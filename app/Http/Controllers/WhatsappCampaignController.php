<?php

namespace App\Http\Controllers;

use App\Models\Clinic;
use App\Models\CompanySetting;
use App\Models\Patient;
use App\Models\WhatsappCampaign;
use App\Services\Communication\CommunicationClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Throwable;

class WhatsappCampaignController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $companyId = (int) $request->session()->get('active_company_id');
        if (! $companyId) {
            abort(403);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:all,inactive,birthday'],
            'message' => ['required', 'string', 'max:2000'],
            'inactive_days' => ['nullable', 'integer', 'min:1', 'max:365'],
        ], [
            'name.required' => 'Informe um nome para identificar a campanha.',
            'type.required' => 'Selecione o publico da campanha.',
            'message.required' => 'Informe a mensagem que sera enviada.',
        ]);

        $inactiveDays = (int) ($data['inactive_days'] ?? 30);
        $patients = $this->campaignPatients($companyId, $data['type'], $inactiveDays);
        $recipients = $this->buildRecipients($patients, $data['message'], $inactiveDays);

        if ($recipients === []) {
            return redirect()
                ->route('settings.whatsapp', ['tab' => 'campanhas'])
                ->withErrors(['campaign' => 'Nenhum cliente com telefone valido foi encontrado para esse publico.'])
                ->withInput();
        }

        $campaign = DB::transaction(function () use ($companyId, $request, $data, $inactiveDays, $recipients): WhatsappCampaign {
            $campaign = WhatsappCampaign::create([
                'company_id' => $companyId,
                'created_by' => $request->user()?->id,
                'name' => $data['name'],
                'type' => $data['type'],
                'status' => 'draft',
                'message' => $data['message'],
                'inactive_days' => $data['type'] === 'inactive' ? $inactiveDays : null,
                'recipients_count' => count($recipients),
            ]);

            $campaign->recipients()->createMany($recipients);

            return $campaign;
        });

        return redirect()
            ->route('settings.whatsapp', ['tab' => 'campanhas'])
            ->with('status', "Campanha preparada com {$campaign->recipients_count} destinatario(s). Revise e clique em Disparar agora para enviar.");
    }

    public function send(Request $request, WhatsappCampaign $campaign, CommunicationClient $communication): RedirectResponse
    {
        $companyId = (int) $request->session()->get('active_company_id');
        if (! $companyId || (int) $campaign->company_id !== $companyId) {
            abort(403);
        }

        if (! $communication->configured()) {
            return redirect()->route('settings.whatsapp', ['tab' => 'campanhas'])
                ->withErrors(['campaign' => 'API de comunicacao nao configurada. Verifique COMMUNICATION_API_URL e COMMUNICATION_API_TOKEN.']);
        }

        $session = $this->whatsappSession($companyId);
        $uuid = (string) ($session['uuid'] ?? '');
        if ($uuid === '') {
            return redirect()->route('settings.whatsapp', ['tab' => 'campanhas'])
                ->withErrors(['campaign' => 'Conecte uma sessao WhatsApp antes de disparar campanhas.']);
        }

        if (in_array($campaign->status, ['sending', 'completed'], true)) {
            return redirect()->route('settings.whatsapp', ['tab' => 'campanhas'])
                ->withErrors(['campaign' => 'Essa campanha ja esta em envio ou ja foi concluida.']);
        }

        $campaign->update([
            'status' => 'sending',
            'started_at' => now(),
            'finished_at' => null,
            'error_message' => null,
        ]);

        $sent = 0;
        $failed = 0;

        $campaign->recipients()
            ->whereIn('status', ['pending', 'failed'])
            ->orderBy('id')
            ->get()
            ->each(function ($recipient) use ($communication, $uuid, &$sent, &$failed): void {
                try {
                    $communication->sendWhatsappMessage($uuid, $recipient->phone, $recipient->message);
                    $recipient->update([
                        'status' => 'sent',
                        'sent_at' => now(),
                        'error_message' => null,
                    ]);
                    $sent++;
                } catch (Throwable $exception) {
                    $recipient->update([
                        'status' => 'failed',
                        'error_message' => mb_substr($exception->getMessage(), 0, 2000),
                    ]);
                    $failed++;
                }
            });

        $totalSent = $campaign->recipients()->where('status', 'sent')->count();
        $totalFailed = $campaign->recipients()->where('status', 'failed')->count();
        $status = $totalFailed > 0 ? 'failed' : 'completed';
        $campaign->update([
            'status' => $status,
            'sent_count' => $totalSent,
            'failed_count' => $totalFailed,
            'finished_at' => now(),
            'error_message' => $totalSent === 0 && $totalFailed > 0 ? 'Nenhuma mensagem foi enviada.' : null,
        ]);

        return redirect()
            ->route('settings.whatsapp', ['tab' => 'campanhas'])
            ->with('status', "Disparo finalizado. Enviadas: {$sent}. Falhas: {$failed}.");
    }

    private function campaignPatients(int $companyId, string $type, int $inactiveDays)
    {
        $query = Patient::query()
            ->whereHas('companies', fn ($companyQuery) => $companyQuery->where('companies.id', $companyId))
            ->where(function ($statusQuery) {
                $statusQuery->whereNull('status')
                    ->orWhere('status', '!=', 'inativo');
            })
            ->orderBy('full_name');

        if ($type === 'birthday') {
            $today = now();
            $query->whereMonth('birthdate', $today->month)
                ->whereDay('birthdate', $today->day);
        }

        $patients = $query->get();

        if ($type !== 'inactive') {
            return $patients;
        }

        $clinicIds = Clinic::query()->where('company_id', $companyId)->pluck('id');
        $cutoff = now()->subDays($inactiveDays);

        return Patient::query()
            ->whereIn('id', $patients->pluck('id'))
            ->withMax([
                'appointments as last_appointment_at' => function ($appointmentQuery) use ($clinicIds) {
                    $appointmentQuery->whereIn('clinic_id', $clinicIds);
                },
            ], 'scheduled_at')
            ->get()
            ->filter(function (Patient $patient) use ($cutoff): bool {
                if (! $patient->last_appointment_at) {
                    return false;
                }

                return Carbon::parse($patient->last_appointment_at)->lte($cutoff);
            })
            ->sortBy('full_name')
            ->values();
    }

    private function buildRecipients($patients, string $message, int $inactiveDays): array
    {
        $recipients = [];
        $seenPhones = [];

        foreach ($patients as $patient) {
            $phone = $this->recipientPhone($patient);
            if (! $phone || isset($seenPhones[$phone])) {
                continue;
            }

            $seenPhones[$phone] = true;
            $recipients[] = [
                'patient_id' => $patient->id,
                'name' => $patient->full_name,
                'phone' => $phone,
                'message' => $this->personalizeMessage($message, $patient, $inactiveDays),
                'status' => 'pending',
            ];
        }

        return $recipients;
    }

    private function recipientPhone(Patient $patient): ?string
    {
        $phone = $patient->whatsapp && $patient->cellphone
            ? $patient->cellphone
            : ($patient->cellphone ?: $patient->phone);

        return $this->normalizeBrazilPhone((string) $phone);
    }

    private function normalizeBrazilPhone(string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '55') && in_array(strlen($digits), [12, 13], true)) {
            return $digits;
        }

        if (in_array(strlen($digits), [10, 11], true)) {
            return '55'.$digits;
        }

        return null;
    }

    private function personalizeMessage(string $message, Patient $patient, int $inactiveDays): string
    {
        $name = trim((string) ($patient->social_name ?: $patient->full_name));
        $firstName = $name !== '' ? explode(' ', $name)[0] : 'cliente';

        return str_replace(
            ['{nome}', '{cliente}', '{primeiro_nome}', '{dias}'],
            [$name !== '' ? $name : 'cliente', $name !== '' ? $name : 'cliente', $firstName, (string) $inactiveDays],
            $message
        );
    }

    private function whatsappSession(int $companyId): ?array
    {
        $value = CompanySetting::query()
            ->where('company_id', $companyId)
            ->where('key', 'whatsapp_session')
            ->value('value');

        if (! $value) {
            return null;
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : null;
    }
}
