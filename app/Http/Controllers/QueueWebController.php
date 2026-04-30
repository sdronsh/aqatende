<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\AccountPayable;
use App\Models\Clinic;
use App\Models\FinancialCategory;
use App\Models\FinancialTransaction;
use App\Models\Patient;
use App\Models\Professional;
use App\Models\Service;
use App\Models\Unit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class QueueWebController extends Controller
{
    public function index(Request $request): View
    {
        $companyId = $this->companyId($request);
        $clinicIds = Clinic::where('company_id', $companyId)->pluck('id');
        $busyProfessionalIds = Appointment::query()
            ->whereIn('clinic_id', $clinicIds)
            ->where('status', 'in_progress')
            ->whereNotNull('professional_id')
            ->pluck('professional_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        return view('queue.index', [
            'waiting' => Appointment::with(['patient', 'service'])
                ->with('services')
                ->whereIn('clinic_id', $clinicIds)
                ->where('status', 'waiting')
                ->orderBy('created_at')
                ->get(),
            'inProgress' => Appointment::with(['patient', 'service', 'professional'])
                ->with('services')
                ->whereIn('clinic_id', $clinicIds)
                ->where('status', 'in_progress')
                ->orderBy('started_at')
                ->get(),
            'patients' => Patient::whereHas('companies', fn ($query) => $query->whereKey($companyId))
                ->orderBy('full_name')
                ->get(),
            'services' => Service::whereIn('clinic_id', $clinicIds)
                ->where('active', true)
                ->orderBy('name')
                ->get(),
            'professionals' => Professional::with('services')
                ->where(function ($query) use ($companyId) {
                    $query->where('company_id', $companyId)
                        ->orWhereHas('user.companies', fn ($companyQuery) => $companyQuery->whereKey($companyId));
                })
                ->where('active', true)
                ->orderBy('display_name')
                ->get(),
            'units' => Unit::whereIn('clinic_id', $clinicIds)->where('active', true)->orderBy('name')->get(),
            'busyProfessionalIds' => $busyProfessionalIds,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $companyId = $this->companyId($request);
        $data = $request->validate([
            'unit_id' => ['required', 'integer', 'exists:units,id'],
            'patient_id' => ['required', 'integer', 'exists:patients,id'],
            'service_id' => ['nullable', 'integer', 'exists:services,id', 'required_without:service_ids'],
            'service_ids' => ['nullable', 'array', 'min:1', 'required_without:service_id'],
            'service_ids.*' => ['integer', 'exists:services,id'],
            'service_professional_ids' => ['nullable', 'array'],
            'service_professional_ids.*' => ['nullable', 'integer', 'exists:professionals,id'],
            'price' => ['nullable'],
            'notes' => ['nullable', 'string'],
        ]);

        $unit = Unit::with('clinic')
            ->whereKey($data['unit_id'])
            ->whereHas('clinic', fn ($query) => $query->where('company_id', $companyId))
            ->firstOrFail();

        $services = $this->resolveSelectedServices($data, (int) $unit->clinic_id);
        $primaryService = $services->first();
        $primaryProfessionalId = $this->resolvePrimaryProfessionalId($data, $services);
        if ($primaryProfessionalId) {
            $this->validateServiceProfessionals($data, $services, $companyId);
        }

        $appointment = Appointment::create([
            'clinic_id' => $unit->clinic_id,
            'unit_id' => $unit->id,
            'patient_id' => $data['patient_id'],
            'service_id' => $primaryService->id,
            'professional_id' => $primaryProfessionalId ?: null,
            'status' => 'waiting',
            'channel' => 'walk_in',
            'scheduled_at' => null,
            'duration_minutes' => (int) $services->sum('duration_minutes'),
            'price_cents' => $request->filled('price') ? $this->moneyToCents($request->input('price')) : (int) $services->sum('price_cents'),
            'payment_status' => 'pending',
            'notes' => $data['notes'] ?? null,
        ]);
        $this->syncAppointmentServices($appointment, $services, $data);

        return back()->with('status', 'Cliente adicionado à fila.');
    }

    public function start(Request $request, Appointment $appointment): RedirectResponse
    {
        $this->authorizeAppointment($request, $appointment);
        $appointment->loadMissing('service');
        abort_unless(in_array($appointment->status, ['waiting', 'scheduled', 'agendado', 'confirmado'], true), 403);

        $data = $request->validate([
            'professional_id' => ['nullable', 'integer', 'exists:professionals,id'],
        ]);

        $serviceIds = $appointment->services()->pluck('services.id')->map(fn ($id) => (int) $id)->all();
        if (! $serviceIds) {
            $serviceIds = [(int) $appointment->service_id];
        }

        $distributedProfessionalIds = $appointment->services()
            ->pluck('appointment_service.professional_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if (! empty($data['professional_id'])) {
            $professional = Professional::whereKey($data['professional_id'])->where('active', true)->firstOrFail();

            if (! $this->professionalCanServeAll($professional, $serviceIds)) {
                return back()
                    ->withErrors(['professional_id' => 'Profissional não atende todos os serviços deste atendimento.'])
                    ->withInput();
            }

            $professionalId = $professional->id;
        } elseif ($distributedProfessionalIds->isNotEmpty()) {
            $professionalId = (int) $distributedProfessionalIds->first();
        } else {
            return back()
                ->withErrors(['professional_id' => 'Selecione um profissional para iniciar o atendimento.'])
                ->withInput();
        }

        $allShared = Service::whereIn('id', $serviceIds)->where('shared_service', false)->doesntExist();
        $busy = ! $allShared
            && Appointment::where('professional_id', $professionalId)
                ->where('status', 'in_progress')
                ->exists();

        if ($busy) {
            return back()
                ->withErrors(['professional_id' => 'Profissional já está em atendimento. Finalize o atendimento atual antes de iniciar outro.'])
                ->withInput();
        }

        $appointment->update([
            'professional_id' => $professionalId,
            'status' => 'in_progress',
            'started_at' => now(),
        ]);

        return back()->with('status', 'Atendimento iniciado.');
    }

    public function finish(Request $request, Appointment $appointment): RedirectResponse
    {
        $this->authorizeAppointment($request, $appointment);
        abort_unless($appointment->status === 'in_progress', 403);

        $data = $request->validate([
            'payment_method' => ['required', 'in:cash,pix,card'],
            'price' => ['nullable'],
        ]);

        DB::transaction(function () use ($appointment, $request, $data) {
            $appointment = Appointment::query()
                ->with(['professional', 'service', 'services'])
                ->whereKey($appointment->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($appointment->status !== 'in_progress') {
                return;
            }

            if ($request->filled('price')) {
                $appointment->price_cents = $this->moneyToCents($request->input('price'));
            }

            $commission = $appointment->calculateCommissionCents();
            $appointment->fill([
                'status' => 'done',
                'finished_at' => now(),
                'payment_status' => 'paid',
                'commission_amount_cents' => $commission,
                'salon_amount_cents' => max(($appointment->price_cents ?? 0) - $commission, 0),
            ])->save();

            FinancialTransaction::create([
                'clinic_id' => $appointment->clinic_id,
                'appointment_id' => $appointment->id,
                'type' => 'income',
                'amount_cents' => $appointment->price_cents ?? 0,
                'payment_method' => $data['payment_method'],
                'description' => 'Atendimento #' . $appointment->id . ' - ' . $appointment->serviceNames(),
                'paid_at' => now(),
            ]);

            $this->syncCommissionPayable($appointment, $commission);
        });

        return back()->with('status', 'Atendimento finalizado.');
    }

    private function professionalCanServeAll(Professional $professional, array $serviceIds): bool
    {
        $allowedIds = $professional->services()
            ->wherePivot('active', true)
            ->pluck('services.id')
            ->map(fn ($id) => (int) $id);

        return collect($serviceIds)->every(fn ($serviceId) => $allowedIds->contains((int) $serviceId));
    }

    private function syncCommissionPayable(Appointment $appointment, int $commissionCents): void
    {
        if ($commissionCents <= 0 || ! $appointment->professional_id) {
            AccountPayable::where('appointment_id', $appointment->id)
                ->where('origem', 'commission')
                ->where('status', 'aberto')
                ->delete();
            return;
        }

        $professional = $appointment->professional()->first();
        if (! $professional) {
            return;
        }

        $categoryId = $this->resolveCommissionCategoryId((int) $appointment->clinic_id);
        $date = $appointment->scheduled_at?->toDateString()
            ?? $appointment->finished_at?->toDateString()
            ?? now()->toDateString();
        $description = 'Comissão profissional - Atendimento #' . $appointment->id;
        $description .= ' - ' . $appointment->serviceNames();

        AccountPayable::updateOrCreate(
            [
                'appointment_id' => $appointment->id,
            ],
            [
                'clinic_id' => $appointment->clinic_id,
                'unit_id' => $appointment->unit_id,
                'professional_id' => $professional->id,
                'categoria_financeira_id' => $categoryId,
                'fornecedor' => $professional->display_name,
                'descricao' => $description,
                'valor_cents' => $commissionCents,
                'data_emissao' => $date,
                'data_vencimento' => $date,
                'status' => 'aberto',
                'centro_custo' => 'Comissões',
                'observacoes' => 'Gerado automaticamente ao finalizar o atendimento.',
                'origem' => 'commission',
            ]
        );
    }

    private function resolveCommissionCategoryId(int $clinicId): ?int
    {
        return FinancialCategory::firstOrCreate(
            [
                'clinic_id' => $clinicId,
                'name' => 'Comissão de profissional',
            ],
            [
                'type' => 'pagar',
                'active' => true,
            ]
        )->id;
    }

    private function authorizeAppointment(Request $request, Appointment $appointment): void
    {
        $companyId = $this->companyId($request);
        abort_unless($appointment->clinic && (int) $appointment->clinic->company_id === $companyId, 403);
    }

    private function companyId(Request $request): int
    {
        return (int) $request->session()->get('active_company_id') ?: abort(403);
    }

    private function resolveSelectedServices(array $data, int $clinicId): \Illuminate\Support\Collection
    {
        $serviceIds = collect($data['service_ids'] ?? [])
            ->filter()
            ->map(fn ($id) => (int) $id);

        if ($serviceIds->isEmpty() && ! empty($data['service_id'])) {
            $serviceIds = collect([(int) $data['service_id']]);
        }

        $serviceIds = $serviceIds->unique()->values();

        $services = Service::where('clinic_id', $clinicId)
            ->whereIn('id', $serviceIds)
            ->where('active', true)
            ->get()
            ->sortBy(fn (Service $service) => $serviceIds->search((int) $service->id))
            ->values();

        if ($services->count() !== $serviceIds->count()) {
            abort(403);
        }

        return $services;
    }

    private function resolvePrimaryProfessionalId(array $data, \Illuminate\Support\Collection $services): ?int
    {
        $professionalMap = $data['service_professional_ids'] ?? [];
        foreach ($services as $service) {
            $professionalId = (int) ($professionalMap[$service->id] ?? 0);
            if ($professionalId > 0) {
                return $professionalId;
            }
        }

        return null;
    }

    private function validateServiceProfessionals(array $data, \Illuminate\Support\Collection $services, int $companyId): void
    {
        $professionalMap = $data['service_professional_ids'] ?? [];

        foreach ($services as $service) {
            $professionalId = (int) ($professionalMap[$service->id] ?? 0);
            if ($professionalId <= 0) {
                continue;
            }

            $professional = Professional::whereKey($professionalId)
                ->where(function ($query) use ($companyId) {
                    $query->where('company_id', $companyId)
                        ->orWhereHas('user.companies', fn ($companyQuery) => $companyQuery->whereKey($companyId));
                })
                ->first();

            if (! $professional || ! $professional->services()->where('services.id', $service->id)->wherePivot('active', true)->exists()) {
                abort(422, 'Um profissional selecionado nao atende o servico informado.');
            }
        }
    }

    private function syncAppointmentServices(Appointment $appointment, \Illuminate\Support\Collection $services, array $data): void
    {
        $sync = [];
        $professionalMap = $data['service_professional_ids'] ?? [];
        foreach ($services->values() as $index => $service) {
            $sync[$service->id] = [
                'professional_id' => (int) ($professionalMap[$service->id] ?? 0) ?: null,
                'duration_minutes' => $service->duration_minutes,
                'price_cents' => $service->price_cents,
                'scheduled_at' => null,
                'ends_at' => null,
                'status' => 'waiting',
                'commission_amount_cents' => 0,
                'position' => $index,
            ];
        }

        $appointment->services()->sync($sync);
    }

    private function moneyToCents(mixed $value): int
    {
        $normalized = str_replace(['.', ','], ['', '.'], preg_replace('/[^\d,.]/', '', (string) $value));

        return max((int) round(((float) $normalized) * 100), 0);
    }
}
