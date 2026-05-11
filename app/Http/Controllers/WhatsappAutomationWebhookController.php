<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Clinic;
use App\Models\CompanySetting;
use App\Models\Patient;
use App\Models\Professional;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\Unit;
use App\Services\Communication\CommunicationClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class WhatsappAutomationWebhookController extends Controller
{
    public function __invoke(Request $request, CommunicationClient $communication): JsonResponse
    {
        $token = (string) config('aqamed.communication.webhook_token', '');
        if ($token !== '' && $request->header('X-Webhook-Token') !== $token) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $payload = $request->all();
        $sessionUuid = (string) (data_get($payload, 'session.uuid') ?? data_get($payload, 'session_uuid') ?? '');
        $phone = preg_replace('/\D+/', '', (string) (data_get($payload, 'message.phone_number') ?? data_get($payload, 'phone_number') ?? '')) ?: '';
        $text = trim((string) (data_get($payload, 'message.body') ?? data_get($payload, 'body') ?? data_get($payload, 'text') ?? ''));

        if ($sessionUuid === '' || $phone === '' || $text === '') {
            return response()->json(['ok' => true, 'ignored' => true]);
        }

        $company = $this->resolveCompanyBySessionUuid($sessionUuid);
        if (! $company) {
            return response()->json(['ok' => true, 'ignored' => true]);
        }

        $automation = $this->getAutomation((int) $company['company_id']);
        if (! (bool) data_get($automation, 'flow.bot_enabled', false)) {
            return response()->json(['ok' => true, 'ignored' => true, 'reason' => 'bot_disabled']);
        }

        $stateKey = "whatsapp_flow_state_{$phone}";
        $state = $this->loadState((int) $company['company_id'], $stateKey);
        $lower = mb_strtolower($text);

        if ($lower === 'menu' || $lower === 'reiniciar') {
            $state = ['step' => 'start'];
        }

        if (($state['step'] ?? 'start') === 'start') {
            if (! str_contains($lower, 'agendar') && ! in_array($lower, ['1', 'oi', 'ola', 'olá'], true)) {
                $this->send($communication, $sessionUuid, $phone, "Oi! Responda *agendar* para iniciar seu agendamento.");
                $this->saveState((int) $company['company_id'], $stateKey, ['step' => 'start']);
                return response()->json(['ok' => true]);
            }

            $services = $this->servicesForCompany((int) $company['company_id']);
            if ($services->isEmpty()) {
                $this->send($communication, $sessionUuid, $phone, 'No momento nao ha servicos ativos para agendamento.');
                return response()->json(['ok' => true]);
            }

            $lines = ["Perfeito. Escolha o servico (responda com o numero):"];
            foreach ($services as $idx => $service) {
                $lines[] = ($idx + 1).'. '.$service->name;
            }
            $this->send($communication, $sessionUuid, $phone, implode("\n", $lines));
            $this->saveState((int) $company['company_id'], $stateKey, [
                'step' => 'service',
                'services' => $services->pluck('id')->all(),
            ]);
            return response()->json(['ok' => true]);
        }

        if (($state['step'] ?? null) === 'service') {
            $index = (int) $text;
            $serviceId = $state['services'][$index - 1] ?? null;
            $service = $serviceId ? Service::find($serviceId) : null;
            if (! $service) {
                $this->send($communication, $sessionUuid, $phone, 'Opcao invalida. Envie o numero do servico.');
                return response()->json(['ok' => true]);
            }

            $professionals = $this->professionalsForService((int) $company['company_id'], $service);
            if ($professionals->isEmpty()) {
                $this->send($communication, $sessionUuid, $phone, 'Nao ha profissionais disponiveis para este servico.');
                return response()->json(['ok' => true]);
            }

            $lines = ["Servico: *{$service->name}*.", 'Escolha o profissional:'];
            $lines[] = '0. Qualquer profissional';
            foreach ($professionals as $idx => $professional) {
                $lines[] = ($idx + 1).'. '.$professional->display_name;
            }
            $this->send($communication, $sessionUuid, $phone, implode("\n", $lines));
            $this->saveState((int) $company['company_id'], $stateKey, [
                'step' => 'professional',
                'service_id' => $service->id,
                'professionals' => $professionals->pluck('id')->all(),
            ]);
            return response()->json(['ok' => true]);
        }

        if (($state['step'] ?? null) === 'professional') {
            $service = Service::find((int) ($state['service_id'] ?? 0));
            if (! $service) {
                $this->saveState((int) $company['company_id'], $stateKey, ['step' => 'start']);
                $this->send($communication, $sessionUuid, $phone, 'Vamos recomecar. Envie *agendar*.');
                return response()->json(['ok' => true]);
            }

            $professionals = $this->professionalsForService((int) $company['company_id'], $service);
            $option = (int) $text;
            $professional = $option === 0
                ? $professionals->first()
                : (($state['professionals'][$option - 1] ?? null) ? $professionals->firstWhere('id', $state['professionals'][$option - 1]) : null);

            if (! $professional) {
                $this->send($communication, $sessionUuid, $phone, 'Opcao invalida. Envie o numero do profissional.');
                return response()->json(['ok' => true]);
            }

            $this->saveState((int) $company['company_id'], $stateKey, [
                'step' => 'datetime',
                'service_id' => $service->id,
                'professional_id' => $professional->id,
            ]);
            $this->send($communication, $sessionUuid, $phone, 'Envie a data e hora desejada no formato *DD/MM HH:MM* (ex: 15/05 14:30).');
            return response()->json(['ok' => true]);
        }

        if (($state['step'] ?? null) === 'datetime') {
            $service = Service::find((int) ($state['service_id'] ?? 0));
            $professional = Professional::find((int) ($state['professional_id'] ?? 0));
            if (! $service || ! $professional) {
                $this->saveState((int) $company['company_id'], $stateKey, ['step' => 'start']);
                $this->send($communication, $sessionUuid, $phone, 'Nao consegui continuar. Envie *agendar* para reiniciar.');
                return response()->json(['ok' => true]);
            }

            $scheduledAt = $this->parseDateTime($text);
            if (! $scheduledAt || $scheduledAt->isPast()) {
                $this->send($communication, $sessionUuid, $phone, 'Data/hora invalida. Use *DD/MM HH:MM* (ex: 15/05 14:30).');
                return response()->json(['ok' => true]);
            }

            $unit = $this->resolveUnitForServiceAndProfessional($service, $professional, (int) $company['company_id']);
            if (! $unit) {
                $this->send($communication, $sessionUuid, $phone, 'Nao encontrei unidade ativa para esse servico.');
                return response()->json(['ok' => true]);
            }

            if (! $this->isProfessionalAvailable($professional->id, $unit->id, $scheduledAt, (int) ($service->duration_minutes ?: 30))) {
                $this->send($communication, $sessionUuid, $phone, 'Horario indisponivel. Envie outro horario no formato *DD/MM HH:MM*.');
                return response()->json(['ok' => true]);
            }

            $patient = $this->resolvePatientByPhone((int) $company['company_id'], $phone);
            if (! $patient) {
                $this->saveState((int) $company['company_id'], $stateKey, [
                    'step' => 'confirm_guest_create',
                    'service_id' => $service->id,
                    'professional_id' => $professional->id,
                    'scheduled_at' => $scheduledAt->toIso8601String(),
                    'unit_id' => $unit->id,
                ]);
                $this->send(
                    $communication,
                    $sessionUuid,
                    $phone,
                    'Nao encontramos seu cadastro. Quer agendar assim mesmo? Responda *sim* ou *nao*.'
                );
                return response()->json(['ok' => true]);
            }

            $appointment = $this->createAppointmentForFlow($service, $professional, $unit, $patient, $scheduledAt);

            $this->saveState((int) $company['company_id'], $stateKey, ['step' => 'start']);
            $this->send(
                $communication,
                $sessionUuid,
                $phone,
                'Agendamento confirmado: '.$scheduledAt->format('d/m/Y H:i').' com '.$professional->display_name.' para '.$service->name.'.'
            );

            return response()->json(['ok' => true, 'appointment_id' => $appointment->id]);
        }

        if (($state['step'] ?? null) === 'confirm_guest_create') {
            if ($this->isAffirmative($lower)) {
                $this->saveState((int) $company['company_id'], $stateKey, array_merge($state, [
                    'step' => 'collect_guest_name',
                ]));
                $this->send($communication, $sessionUuid, $phone, 'Perfeito. Qual o seu nome completo?');
                return response()->json(['ok' => true]);
            }

            if ($this->isNegative($lower)) {
                $this->saveState((int) $company['company_id'], $stateKey, ['step' => 'start']);
                $this->send($communication, $sessionUuid, $phone, 'Sem problema. Quando quiser, envie *agendar* para iniciar novamente.');
                return response()->json(['ok' => true]);
            }

            $this->send($communication, $sessionUuid, $phone, 'Responda *sim* para continuar ou *nao* para cancelar.');
            return response()->json(['ok' => true]);
        }

        if (($state['step'] ?? null) === 'collect_guest_name') {
            $name = trim($text);
            if (mb_strlen($name) < 3) {
                $this->send($communication, $sessionUuid, $phone, 'Nome muito curto. Informe seu nome completo.');
                return response()->json(['ok' => true]);
            }

            $service = Service::find((int) ($state['service_id'] ?? 0));
            $professional = Professional::find((int) ($state['professional_id'] ?? 0));
            $unit = Unit::find((int) ($state['unit_id'] ?? 0));
            $scheduledAt = isset($state['scheduled_at']) ? Carbon::parse((string) $state['scheduled_at']) : null;

            if (! $service || ! $professional || ! $unit || ! $scheduledAt) {
                $this->saveState((int) $company['company_id'], $stateKey, ['step' => 'start']);
                $this->send($communication, $sessionUuid, $phone, 'Nao consegui concluir. Envie *agendar* para recomecar.');
                return response()->json(['ok' => true]);
            }

            $patient = $this->createPatientForFlow((int) $company['company_id'], $name, $phone);
            $appointment = $this->createAppointmentForFlow($service, $professional, $unit, $patient, $scheduledAt);

            $this->saveState((int) $company['company_id'], $stateKey, ['step' => 'start']);
            $this->send(
                $communication,
                $sessionUuid,
                $phone,
                'Cadastro concluido e agendamento confirmado: '.$scheduledAt->format('d/m/Y H:i').' com '.$professional->display_name.' para '.$service->name.'.'
            );

            return response()->json(['ok' => true, 'appointment_id' => $appointment->id, 'patient_id' => $patient->id]);
        }

        $this->saveState((int) $company['company_id'], $stateKey, ['step' => 'start']);
        return response()->json(['ok' => true]);
    }

    private function send(CommunicationClient $communication, string $sessionUuid, string $phone, string $text): void
    {
        $communication->sendWhatsappMessage($sessionUuid, $phone, $text);
    }

    private function resolveCompanyBySessionUuid(string $uuid): ?array
    {
        $rows = CompanySetting::query()
            ->where('key', 'whatsapp_session')
            ->get(['company_id', 'value']);

        foreach ($rows as $row) {
            $decoded = json_decode((string) $row->value, true);
            if (is_array($decoded) && (string) ($decoded['uuid'] ?? '') === $uuid) {
                return ['company_id' => (int) $row->company_id];
            }
        }

        return null;
    }

    private function servicesForCompany(int $companyId)
    {
        $clinicIds = Clinic::query()->where('company_id', $companyId)->pluck('id');
        return Service::query()
            ->whereIn('clinic_id', $clinicIds)
            ->where('active', true)
            ->orderBy('name')
            ->limit(9)
            ->get(['id', 'name']);
    }

    private function professionalsForService(int $companyId, Service $service)
    {
        return Professional::query()
            ->where('active', true)
            ->where(function ($query) use ($companyId) {
                $query->where('company_id', $companyId)
                    ->orWhereHas('user.companies', fn ($companyQuery) => $companyQuery->whereKey($companyId));
            })
            ->whereHas('services', function ($query) use ($service) {
                $query->where('services.id', $service->id)
                    ->where('professional_service.active', true);
            })
            ->orderBy('display_name')
            ->limit(9)
            ->get(['id', 'display_name']);
    }

    private function resolveUnitForServiceAndProfessional(Service $service, Professional $professional, int $companyId): ?Unit
    {
        $clinicIds = Clinic::query()->where('company_id', $companyId)->pluck('id');

        return Unit::query()
            ->whereIn('clinic_id', $clinicIds)
            ->where('active', true)
            ->when($service->unit_id, fn ($query) => $query->whereKey($service->unit_id))
            ->whereHas('professionals', fn ($query) => $query->where('professionals.id', $professional->id))
            ->orderBy('name')
            ->first();
    }

    private function isProfessionalAvailable(int $professionalId, int $unitId, Carbon $start, int $duration): bool
    {
        $end = $start->copy()->addMinutes($duration);

        $hasConflict = Appointment::query()
            ->where('professional_id', $professionalId)
            ->where('unit_id', $unitId)
            ->whereNotIn('status', ['cancelado', 'cancelled'])
            ->where(function ($query) use ($start, $end) {
                $query->whereBetween('scheduled_at', [$start, $end->copy()->subSecond()])
                    ->orWhereBetween('ends_at', [$start->copy()->addSecond(), $end])
                    ->orWhere(function ($inner) use ($start, $end) {
                        $inner->where('scheduled_at', '<', $start)->where('ends_at', '>', $end);
                    });
            })
            ->exists();

        if ($hasConflict) {
            return false;
        }

        $schedules = Schedule::query()
            ->where('professional_id', $professionalId)
            ->where('unit_id', $unitId)
            ->where('is_active', true)
            ->get();

        if ($schedules->isEmpty()) {
            return true;
        }

        $weekday = $start->dayOfWeekIso;
        $daySchedules = $schedules->where('weekday', $weekday);
        if ($daySchedules->isEmpty()) {
            return false;
        }

        $startMinutes = ((int) $start->format('H')) * 60 + ((int) $start->format('i'));
        $endMinutes = ((int) $end->format('H')) * 60 + ((int) $end->format('i'));

        foreach ($daySchedules as $schedule) {
            [$startHour, $startMin] = array_map('intval', explode(':', substr((string) $schedule->start_time, 0, 5)));
            [$endHour, $endMin] = array_map('intval', explode(':', substr((string) $schedule->end_time, 0, 5)));
            $slotStart = ($startHour * 60) + $startMin;
            $slotEnd = ($endHour * 60) + $endMin;
            if ($startMinutes >= $slotStart && $endMinutes <= $slotEnd) {
                return true;
            }
        }

        return false;
    }

    private function resolvePatientByPhone(int $companyId, string $phone): ?Patient
    {
        $incomingCandidates = $this->phoneCandidates($phone);

        $patients = Patient::query()
            ->select(['id', 'cellphone'])
            ->whereHas('companies', fn ($query) => $query->where('companies.id', $companyId))
            ->orderBy('id')
            ->get();

        foreach ($patients as $patient) {
            $patientCandidates = $this->phoneCandidates((string) ($patient->cellphone ?? ''));
            if (! empty(array_intersect($incomingCandidates, $patientCandidates))) {
                return $patient;
            }
        }

        return null;
    }

    private function phoneCandidates(string $value): array
    {
        $digits = preg_replace('/\D+/', '', $value) ?: '';
        if ($digits === '') {
            return [];
        }

        $set = [];
        $set[$digits] = true;

        if (str_starts_with($digits, '55')) {
            if (strlen($digits) === 13 && ($digits[4] ?? '') === '9') {
                $set[substr($digits, 0, 4).substr($digits, 5)] = true;
            }

            if (strlen($digits) === 12) {
                $set[substr($digits, 0, 4).'9'.substr($digits, 4)] = true;
            }

            $local = substr($digits, 2);
            $set[$local] = true;
            if (strlen($local) === 11 && ($local[2] ?? '') === '9') {
                $set[substr($local, 0, 2).substr($local, 3)] = true;
            }
            if (strlen($local) === 10) {
                $set[substr($local, 0, 2).'9'.substr($local, 2)] = true;
            }
        } else {
            $set['55'.$digits] = true;
            if (strlen($digits) === 11 && ($digits[2] ?? '') === '9') {
                $set['55'.substr($digits, 0, 2).substr($digits, 3)] = true;
            }
            if (strlen($digits) === 10) {
                $set['55'.substr($digits, 0, 2).'9'.substr($digits, 2)] = true;
            }
        }

        return array_keys($set);
    }

    private function parseDateTime(string $text): ?Carbon
    {
        if (! preg_match('/^\s*(\d{2})\/(\d{2})\s+(\d{2}):(\d{2})\s*$/', $text, $matches)) {
            return null;
        }

        $timezone = (string) config('aqamed.whatsapp.timezone', 'America/Sao_Paulo');
        $year = now()->year;
        $candidate = Carbon::createFromFormat(
            'd/m/Y H:i',
            "{$matches[1]}/{$matches[2]}/{$year} {$matches[3]}:{$matches[4]}",
            $timezone
        );

        return $candidate;
    }

    private function loadState(int $companyId, string $key): array
    {
        $value = CompanySetting::query()->where('company_id', $companyId)->where('key', $key)->value('value');
        $decoded = is_string($value) ? json_decode($value, true) : null;
        return is_array($decoded) ? $decoded : ['step' => 'start'];
    }

    private function saveState(int $companyId, string $key, array $state): void
    {
        CompanySetting::updateOrCreate(
            ['company_id' => $companyId, 'key' => $key],
            ['value' => json_encode($state)]
        );
    }

    private function getAutomation(int $companyId): array
    {
        $raw = CompanySetting::query()
            ->where('company_id', $companyId)
            ->where('key', 'whatsapp_automation')
            ->value('value');

        $decoded = is_string($raw) ? json_decode($raw, true) : null;
        return is_array($decoded) ? $decoded : [];
    }

    private function isAffirmative(string $value): bool
    {
        $value = trim(mb_strtolower($value));
        return in_array($value, ['sim', 's', 'ok', 'pode', 'yes', 'y'], true);
    }

    private function isNegative(string $value): bool
    {
        $value = trim(mb_strtolower($value));
        return in_array($value, ['nao', 'não', 'n', 'cancelar', 'no'], true);
    }

    private function createPatientForFlow(int $companyId, string $name, string $phone): Patient
    {
        $digits = $this->normalizeBrazilLocalPhone($phone);
        $digits = $this->normalizeBrazilMobileDigits($digits);
        $formatted = $this->formatBrazilPhone($digits);

        $patient = Patient::create([
            'full_name' => $name,
            'social_name' => null,
            'status' => 'ativo',
            'cellphone' => $formatted,
            'phone' => $formatted,
            'whatsapp' => true,
            'admin_notes' => 'Cadastro criado automaticamente via WhatsApp.',
            'created_by_name' => 'WhatsApp Bot',
        ]);
        $patient->companies()->syncWithoutDetaching([$companyId]);

        return $patient;
    }

    private function normalizeBrazilLocalPhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?: '';
        if ($digits === '') {
            return '';
        }

        if (str_starts_with($digits, '55') && strlen($digits) >= 12) {
            $digits = substr($digits, 2);
        }

        return $digits;
    }

    private function normalizeBrazilMobileDigits(string $digits): string
    {
        if (strlen($digits) === 10) {
            return substr($digits, 0, 2).'9'.substr($digits, 2);
        }

        if (strlen($digits) > 11) {
            $digits = substr($digits, -11);
        }

        return $digits;
    }

    private function formatBrazilPhone(string $digits): string
    {
        if (strlen($digits) === 11) {
            return sprintf('(%s) %s-%s', substr($digits, 0, 2), substr($digits, 2, 5), substr($digits, 7, 4));
        }

        if (strlen($digits) === 10) {
            return sprintf('(%s) %s-%s', substr($digits, 0, 2), substr($digits, 2, 4), substr($digits, 6, 4));
        }

        return $digits;
    }

    private function createAppointmentForFlow(Service $service, Professional $professional, Unit $unit, Patient $patient, Carbon $scheduledAt): Appointment
    {
        $duration = (int) ($service->duration_minutes ?: 30);

        return Appointment::create([
            'clinic_id' => $unit->clinic_id,
            'unit_id' => $unit->id,
            'professional_id' => $professional->id,
            'patient_id' => $patient->id,
            'service_id' => $service->id,
            'status' => 'agendado',
            'channel' => 'whatsapp',
            'scheduled_at' => $scheduledAt,
            'ends_at' => $scheduledAt->copy()->addMinutes($duration),
            'duration_minutes' => $duration,
            'notes' => 'Agendado automaticamente via WhatsApp.',
            'price_cents' => (int) ($service->price_cents ?? 0),
            'payment_status' => 'pending',
        ]);
    }
}
