<?php

namespace App\Http\Controllers;

use App\Models\AccountReceivable;
use App\Models\Appointment;
use App\Models\Clinic;
use App\Models\FinancialCategory;
use App\Models\PatientBookingLink;
use App\Models\Professional;
use App\Models\Schedule;
use App\Models\ScheduleBlock;
use App\Models\Service;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PublicBookingController extends Controller
{
    public function show(Request $request, string $token): View
    {
        $bookingLink = $this->resolveBookingLink($token);
        $companyId = (int) $bookingLink->company_id;
        $clinicIds = Clinic::where('company_id', $companyId)->pluck('id');

        $services = Service::query()
            ->whereIn('clinic_id', $clinicIds)
            ->where('active', true)
            ->orderBy('name')
            ->get();

        $selectedService = $request->integer('service_id')
            ? $services->firstWhere('id', $request->integer('service_id'))
            : null;

        $units = $this->availableUnits($clinicIds, $selectedService);
        $selectedUnit = $request->integer('unit_id')
            ? $units->firstWhere('id', $request->integer('unit_id'))
            : ($units->count() === 1 ? $units->first() : null);

        $professionals = $selectedService
            ? $this->availableProfessionals($companyId, $selectedService, $selectedUnit)
            : collect();
        $selectedProfessional = $request->integer('professional_id')
            ? $professionals->firstWhere('id', $request->integer('professional_id'))
            : null;

        $date = Carbon::parse($request->string('date', now()->addDay()->toDateString())->toString())->startOfDay();
        if ($date->isBefore(now()->startOfDay())) {
            $date = now()->addDay()->startOfDay();
        }
        $availableDays = collect(range(0, 13))
            ->map(fn ($offset) => now()->startOfDay()->addDays($offset));

        $slots = ($selectedService && $selectedUnit)
            ? $this->availableSlots($selectedService, $selectedUnit, $professionals, $date, $selectedProfessional)
            : collect();

        return view('public-booking.show', [
            'bookingLink' => $bookingLink->load(['patient', 'company']),
            'services' => $services,
            'units' => $units,
            'professionals' => $professionals,
            'selectedService' => $selectedService,
            'selectedUnit' => $selectedUnit,
            'selectedProfessional' => $selectedProfessional,
            'date' => $date,
            'availableDays' => $availableDays,
            'slots' => $slots,
        ]);
    }

    public function store(Request $request, string $token): View
    {
        $bookingLink = $this->resolveBookingLink($token);
        $companyId = (int) $bookingLink->company_id;
        $clinicIds = Clinic::where('company_id', $companyId)->pluck('id');

        $data = $request->validate([
            'service_id' => ['required', 'integer', 'exists:services,id'],
            'unit_id' => ['required', 'integer', 'exists:units,id'],
            'slot' => ['required', 'string'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        [$professionalId, $scheduledAtValue] = array_pad(explode('|', $data['slot'], 2), 2, null);
        abort_unless($professionalId && $scheduledAtValue, 422);

        $service = Service::query()
            ->whereKey($data['service_id'])
            ->whereIn('clinic_id', $clinicIds)
            ->where('active', true)
            ->firstOrFail();

        $unit = Unit::query()
            ->whereKey($data['unit_id'])
            ->whereIn('clinic_id', $clinicIds)
            ->where('active', true)
            ->firstOrFail();

        $professional = $this->availableProfessionals($companyId, $service, $unit)
            ->firstWhere('id', (int) $professionalId);
        abort_unless($professional, 422);

        $scheduledAt = Carbon::parse($scheduledAtValue);
        $date = $scheduledAt->copy()->startOfDay();
        $slotIsAvailable = $this->availableSlots($service, $unit, collect([$professional]), $date, $professional)
            ->contains(fn ($slot) => $slot['scheduled_at']->equalTo($scheduledAt));
        abort_unless($slotIsAvailable, 422);

        $appointment = DB::transaction(function () use ($bookingLink, $service, $unit, $professional, $scheduledAt, $data): Appointment {
            $duration = (int) ($service->duration_minutes ?: 30);
            $endsAt = $scheduledAt->copy()->addMinutes($duration);

            $appointment = Appointment::create([
                'clinic_id' => $unit->clinic_id,
                'unit_id' => $unit->id,
                'professional_id' => $professional->id,
                'patient_id' => $bookingLink->patient_id,
                'service_id' => $service->id,
                'status' => 'agendado',
                'channel' => 'public_link',
                'scheduled_at' => $scheduledAt,
                'ends_at' => $endsAt,
                'duration_minutes' => $duration,
                'notes' => $data['notes'] ?? null,
                'price_cents' => $service->price_cents,
                'payment_status' => 'pending',
            ]);

            $appointment->services()->sync([
                $service->id => [
                    'professional_id' => $professional->id,
                    'duration_minutes' => $duration,
                    'price_cents' => $service->price_cents,
                    'scheduled_at' => $scheduledAt,
                    'ends_at' => $endsAt,
                    'status' => 'agendado',
                    'commission_amount_cents' => 0,
                    'position' => 0,
                ],
            ]);

            AccountReceivable::firstOrCreate(
                ['appointment_id' => $appointment->id],
                [
                    'clinic_id' => $appointment->clinic_id,
                    'unit_id' => $appointment->unit_id,
                    'professional_id' => $appointment->professional_id,
                    'patient_id' => $appointment->patient_id,
                    'categoria_financeira_id' => $this->resolveReceivableCategoryId((int) $appointment->clinic_id),
                    'descricao' => 'Atendimento ' . $service->name,
                    'valor_total_cents' => $service->price_cents,
                    'numero_parcelas' => 1,
                    'numero_parcela' => 1,
                    'valor_parcela_cents' => $service->price_cents,
                    'data_emissao' => now()->toDateString(),
                    'data_vencimento' => $appointment->scheduled_at->toDateString(),
                    'status' => 'aberto',
                ]
            );

            $bookingLink->update(['used_at' => now()]);

            return $appointment->load(['patient', 'professional', 'service', 'unit', 'clinic']);
        });

        return view('public-booking.success', [
            'appointment' => $appointment,
            'company' => $bookingLink->company,
        ]);
    }

    private function resolveBookingLink(string $token): PatientBookingLink
    {
        $bookingLink = PatientBookingLink::query()
            ->with(['company', 'patient'])
            ->where('token', $token)
            ->firstOrFail();

        abort_unless($bookingLink->isAvailable(), 410);

        return $bookingLink;
    }

    private function availableUnits(Collection $clinicIds, ?Service $service): Collection
    {
        return Unit::query()
            ->whereIn('clinic_id', $clinicIds)
            ->when($service?->unit_id, fn ($query) => $query->whereKey($service->unit_id))
            ->where('active', true)
            ->orderBy('name')
            ->get();
    }

    private function availableProfessionals(int $companyId, Service $service, ?Unit $unit): Collection
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
            ->when($unit, fn ($query) => $query->whereHas('units', fn ($unitQuery) => $unitQuery->whereKey($unit->id)))
            ->orderBy('display_name')
            ->get();
    }

    private function availableSlots(Service $service, Unit $unit, Collection $professionals, Carbon $date, ?Professional $selectedProfessional): Collection
    {
        $duration = (int) ($service->duration_minutes ?: 30);
        $professionalIds = $professionals
            ->when($selectedProfessional, fn ($items) => $items->where('id', $selectedProfessional->id))
            ->pluck('id')
            ->values();

        if ($professionalIds->isEmpty()) {
            return collect();
        }

        $schedules = Schedule::query()
            ->whereIn('professional_id', $professionalIds)
            ->where('unit_id', $unit->id)
            ->where('weekday', $date->dayOfWeekIso)
            ->where('is_active', true)
            ->get();
        $professionalsWithAnySchedule = Schedule::query()
            ->whereIn('professional_id', $professionalIds)
            ->where('unit_id', $unit->id)
            ->where('is_active', true)
            ->distinct()
            ->pluck('professional_id')
            ->map(fn ($id) => (int) $id);

        $dayStart = $date->copy()->startOfDay();
        $dayEnd = $date->copy()->endOfDay();
        $appointments = Appointment::query()
            ->whereIn('professional_id', $professionalIds)
            ->where('unit_id', $unit->id)
            ->whereBetween('scheduled_at', [$dayStart, $dayEnd])
            ->whereNotIn('status', ['cancelado', 'cancelled'])
            ->get(['professional_id', 'scheduled_at', 'ends_at', 'duration_minutes']);
        $blocks = ScheduleBlock::query()
            ->whereIn('professional_id', $professionalIds)
            ->where(function ($query) use ($unit) {
                $query->whereNull('unit_id')->orWhere('unit_id', $unit->id);
            })
            ->where('starts_at', '<=', $dayEnd)
            ->where('ends_at', '>=', $dayStart)
            ->get(['professional_id', 'starts_at', 'ends_at']);

        return $professionalIds
            ->flatMap(function (int $professionalId) use ($professionals, $professionalsWithAnySchedule, $schedules, $date, $duration, $appointments, $blocks) {
                $professional = $professionals->firstWhere('id', $professionalId);
                if (! $professional) {
                    return [];
                }

                $daySchedules = $schedules->where('professional_id', $professionalId);
                if ($daySchedules->isEmpty() && ! $professionalsWithAnySchedule->contains($professionalId)) {
                    $daySchedules = collect([(object) [
                        'start_time' => '00:00',
                        'end_time' => '23:59',
                    ]]);
                }

                $slots = [];
                foreach ($daySchedules as $schedule) {
                    $cursor = $date->copy()->setTimeFromTimeString(substr((string) $schedule->start_time, 0, 5));
                    $end = $date->copy()->setTimeFromTimeString(substr((string) $schedule->end_time, 0, 5));

                    while ($cursor->copy()->addMinutes($duration)->lessThanOrEqualTo($end)) {
                        $slotStart = $cursor->copy();
                        $slotEnd = $slotStart->copy()->addMinutes($duration);

                        if ($slotStart->isFuture() && ! $this->hasConflict($professional->id, $slotStart, $slotEnd, $appointments, $blocks)) {
                            $slots[] = [
                                'professional' => $professional,
                                'scheduled_at' => $slotStart,
                                'ends_at' => $slotEnd,
                                'label' => $slotStart->format('H:i') . ' - ' . $professional->display_name,
                            ];
                        }

                        $cursor->addMinutes(30);
                    }
                }

                return $slots;
            })
            ->sortBy(fn ($slot) => $slot['scheduled_at']->timestamp . '-' . $slot['professional']->display_name)
            ->values();
    }

    private function hasConflict(int $professionalId, Carbon $slotStart, Carbon $slotEnd, Collection $appointments, Collection $blocks): bool
    {
        $appointmentConflict = $appointments
            ->where('professional_id', $professionalId)
            ->contains(function (Appointment $appointment) use ($slotStart, $slotEnd) {
                $start = $appointment->scheduled_at;
                $end = $appointment->ends_at ?: $start->copy()->addMinutes((int) ($appointment->duration_minutes ?: 30));

                return $start < $slotEnd && $end > $slotStart;
            });

        if ($appointmentConflict) {
            return true;
        }

        return $blocks
            ->where('professional_id', $professionalId)
            ->contains(fn (ScheduleBlock $block) => $block->starts_at < $slotEnd && $block->ends_at > $slotStart);
    }

    private function resolveReceivableCategoryId(int $clinicId): ?int
    {
        $category = FinancialCategory::query()
            ->where('clinic_id', $clinicId)
            ->where('type', 'receber')
            ->where(function ($query) {
                $query->where('name', 'like', 'Atendimento%')
                    ->orWhere('name', 'like', 'Atendimentos%')
                    ->orWhere('name', 'like', 'Consulta%')
                    ->orWhere('name', 'like', 'Consultas%');
            })
            ->first();

        return $category?->id ?: FinancialCategory::query()
            ->where('clinic_id', $clinicId)
            ->where('type', 'receber')
            ->orderBy('name')
            ->value('id');
    }
}
