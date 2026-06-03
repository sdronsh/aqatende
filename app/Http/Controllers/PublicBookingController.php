<?php

namespace App\Http\Controllers;

use App\Models\AccountReceivable;
use App\Models\Appointment;
use App\Models\Clinic;
use App\Models\FinancialCategory;
use App\Models\Patient;
use App\Models\PatientBookingLink;
use App\Models\Professional;
use App\Models\Schedule;
use App\Models\ScheduleBlock;
use App\Models\Service;
use App\Models\Unit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PublicBookingController extends Controller
{
    public function show(Request $request, string $token): View
    {
        $bookingLink = $this->resolveBookingLink($token);
        $companyId = (int) $bookingLink->company_id;
        $clinicIds = Clinic::where('company_id', $companyId)->pluck('id');
        $continuationAppointment = $this->continuationAppointment($request, $bookingLink);
        $pendingItems = $this->pendingBookingItems($request, $token);
        $nextStartAt = $continuationAppointment?->ends_at ?: $this->lastBookingItemEndsAt($pendingItems);

        $services = Service::with('packageItems')
            ->whereIn('clinic_id', $clinicIds)
            ->when($continuationAppointment?->unit_id, function ($query) use ($continuationAppointment) {
                $query->where(function ($inner) use ($continuationAppointment) {
                    $inner->whereNull('unit_id')->orWhere('unit_id', $continuationAppointment->unit_id);
                });
            })
            ->where('active', true)
            ->orderBy('name')
            ->get();

        $selectedService = $request->integer('service_id')
            ? $services->firstWhere('id', $request->integer('service_id'))
            : null;

        $units = $this->availableUnits($clinicIds, $selectedService);
        $selectedUnit = $continuationAppointment?->unit
            ?: ($request->integer('unit_id')
            ? $units->firstWhere('id', $request->integer('unit_id'))
            : ($units->count() === 1 ? $units->first() : null));

        $professionals = $selectedService
            ? $this->availableProfessionals($companyId, $selectedService, $selectedUnit)
            : collect();
        $selectedProfessional = $request->integer('professional_id')
            ? $professionals->firstWhere('id', $request->integer('professional_id'))
            : null;

        $now = $this->bookingNow();
        $dateValue = $nextStartAt?->toDateString() ?: $request->string('date', $now->copy()->addDay()->toDateString())->toString();
        $date = Carbon::parse($dateValue, $this->bookingTimezone())->startOfDay();
        if ($date->isBefore($now->copy()->startOfDay())) {
            $date = $now->copy()->addDay()->startOfDay();
        }
        $availableDays = collect(range(0, 13))
            ->map(fn ($offset) => $now->copy()->startOfDay()->addDays($offset));

        $slots = ($selectedService && $selectedUnit)
            ? $this->availableSlots($selectedService, $selectedUnit, $professionals, $date, $selectedProfessional, $nextStartAt)
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
            'pendingItems' => $pendingItems,
            'nextStartAt' => $nextStartAt,
            'continuationAppointment' => $continuationAppointment,
        ]);
    }

    public function store(Request $request, string $token): View|RedirectResponse
    {
        $bookingLink = $this->resolveBookingLink($token);
        $companyId = (int) $bookingLink->company_id;
        $clinicIds = Clinic::where('company_id', $companyId)->pluck('id');
        $continuationAppointment = $this->continuationAppointment($request, $bookingLink);

        $bookingAction = $request->input('booking_action', 'finish');

        $rules = [
            'service_id' => ['required', 'integer', 'exists:services,id'],
            'unit_id' => ['required', 'integer', 'exists:units,id'],
            'slot' => ['required', 'string'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'booking_action' => ['nullable', 'in:finish,add_more'],
        ];

        if (! $bookingLink->patient_id && ! $continuationAppointment && $bookingAction === 'finish') {
            $rules['customer_name'] = ['required', 'string', 'max:255'];
            $rules['customer_phone'] = ['nullable', 'string', 'max:30'];
        }

        $data = $request->validate($rules, [
            'customer_name.required' => 'Informe seu nome para concluir o agendamento.',
        ]);

        [$slotProfessionalValue, $scheduledAtValue] = array_pad(explode('|', $data['slot'], 2), 2, null);
        abort_unless($slotProfessionalValue && $scheduledAtValue, 422);

        $service = Service::with('packageItems')
            ->whereKey($data['service_id'])
            ->whereIn('clinic_id', $clinicIds)
            ->where('active', true)
            ->firstOrFail();

        $unit = Unit::query()
            ->whereKey($data['unit_id'])
            ->whereIn('clinic_id', $clinicIds)
            ->where('active', true)
            ->firstOrFail();
        abort_if($service->unit_id && (int) $service->unit_id !== (int) $unit->id, 422);
        abort_if($continuationAppointment && (int) $unit->id !== (int) $continuationAppointment->unit_id, 422);

        $scheduledAt = Carbon::parse($scheduledAtValue, $this->bookingTimezone());
        $date = $scheduledAt->copy()->startOfDay();
        $professionals = $this->availableProfessionals($companyId, $service, $unit);
        $professional = null;
        $serviceProfessionalIds = [];

        if ($service->is_package) {
            $serviceProfessionalIds = $this->decodePackageSlotAssignments($slotProfessionalValue);
        } else {
            $professional = $professionals->firstWhere('id', (int) $slotProfessionalValue);
            abort_unless($professional, 422);
            $serviceProfessionalIds = [$service->id => (int) $professional->id];
        }

        $pendingItems = $this->pendingBookingItems($request, $token);
        $nextStartAt = $continuationAppointment?->ends_at ?: $this->lastBookingItemEndsAt($pendingItems);

        $slotIsAvailable = $this->availableSlots($service, $unit, $professionals, $date, $professional, $nextStartAt)
            ->contains(function ($slot) use ($service, $serviceProfessionalIds, $scheduledAt, $data) {
                if ($slot['scheduled_at']->toDateTimeString() !== $scheduledAt->toDateTimeString()) {
                    return false;
                }

                if ($service->is_package) {
                    return $slot['value'] === $data['slot'];
                }

                return (int) $slot['professional']->id === (int) collect($serviceProfessionalIds)->first();
            });
        abort_unless($slotIsAvailable, 422, 'Horario indisponivel para o servico selecionado.');

        $currentItems = $this->buildBookingItems($service, $unit, $professionals, $serviceProfessionalIds, $scheduledAt);

        if (($data['booking_action'] ?? 'finish') === 'add_more') {
            $this->appendPendingBookingItems($request, $token, $currentItems);

            return redirect()
                ->route('public.booking.show', [
                    'token' => $token,
                    'date' => Carbon::parse(collect($currentItems)->last()['ends_at'])->toDateString(),
                ])
                ->with('status', 'Servico incluido. Escolha o proximo servico para agendar em seguida.');
        }

        $patient = $continuationAppointment?->patient ?: ($bookingLink->patient_id
            ? $bookingLink->patient
            : $this->resolveOrCreatePatientForPublicBooking(
                $companyId,
                (string) ($data['customer_name'] ?? ''),
                (string) ($data['customer_phone'] ?? '')
            ));

        $items = $pendingItems->merge($currentItems)->values();
        $appointment = DB::transaction(function () use ($bookingLink, $patient, $items, $data): Appointment {
            $appointment = $this->createAppointmentFromBookingItems($patient, $items, 'public_link', $data['notes'] ?? null);
            if ($bookingLink->patient_id) {
                $bookingLink->update(['used_at' => now()]);
            }

            return $appointment->load(['patient', 'professional', 'service', 'unit', 'clinic']);
        });
        $this->clearPendingBookingItems($request, $token);
        $this->clearContinuationAppointment($request, $bookingLink);

        return view('public-booking.success', [
            'appointment' => $appointment,
            'previousAppointment' => $continuationAppointment?->load(['patient', 'professional', 'service', 'services', 'unit', 'clinic']),
            'company' => $bookingLink->company,
            'newBookingUrl' => $this->newBookingUrlAfterSuccess($request, $bookingLink, $appointment),
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

    private function bookingTimezone(): string
    {
        return (string) config('aqamed.booking.timezone', 'America/Sao_Paulo');
    }

    private function bookingNow(): Carbon
    {
        return now($this->bookingTimezone());
    }

    private function slotIsAfterNow(Carbon $slotStart): bool
    {
        return $slotStart->greaterThan($this->bookingNow());
    }

    private function bookingSlotIntervalMinutes(): int
    {
        return 15;
    }

    private function bookingDateTime($value): Carbon
    {
        if ($value instanceof Carbon) {
            return Carbon::parse($value->toDateTimeString(), $this->bookingTimezone());
        }

        return Carbon::parse((string) $value, $this->bookingTimezone());
    }

    private function newBookingUrlAfterSuccess(Request $request, PatientBookingLink $bookingLink, Appointment $appointment): string
    {
        if (! $bookingLink->patient_id) {
            $this->storeContinuationAppointment($request, $bookingLink->token, $appointment);

            return route('public.booking.show', $bookingLink->token);
        }

        $newLink = PatientBookingLink::create([
            'company_id' => $bookingLink->company_id,
            'patient_id' => $bookingLink->patient_id,
            'created_by' => null,
            'token' => Str::random(48),
            'expires_at' => now()->addDays(7),
        ]);
        $this->storeContinuationAppointment($request, $newLink->token, $appointment);

        return route('public.booking.show', $newLink->token);
    }

    private function continuationKey(string $token): string
    {
        return "public_booking_continue_after_{$token}";
    }

    private function storeContinuationAppointment(Request $request, string $token, Appointment $appointment): void
    {
        $request->session()->put($this->continuationKey($token), $appointment->id);
    }

    private function continuationAppointment(Request $request, PatientBookingLink $bookingLink): ?Appointment
    {
        $appointmentId = (int) $request->session()->get($this->continuationKey($bookingLink->token), 0);
        if ($appointmentId <= 0) {
            return null;
        }

        $clinicIds = Clinic::query()
            ->where('company_id', $bookingLink->company_id)
            ->pluck('id');

        return Appointment::query()
            ->with(['patient', 'professional', 'service', 'services', 'unit', 'clinic'])
            ->whereKey($appointmentId)
            ->whereIn('clinic_id', $clinicIds)
            ->when($bookingLink->patient_id, fn ($query) => $query->where('patient_id', $bookingLink->patient_id))
            ->whereNotIn('status', ['cancelado', 'cancelled'])
            ->first();
    }

    private function clearContinuationAppointment(Request $request, PatientBookingLink $bookingLink): void
    {
        $request->session()->forget($this->continuationKey($bookingLink->token));
    }

    private function resolveOrCreatePatientForPublicBooking(int $companyId, string $name, string $phone): Patient
    {
        $name = trim($name);
        $formattedPhone = $this->formatBrazilPhone($phone);
        $digits = preg_replace('/\D+/', '', $formattedPhone) ?: '';

        if ($digits !== '') {
            $patient = Patient::query()
                ->whereHas('companies', fn ($query) => $query->whereKey($companyId))
                ->where(function ($query) use ($digits) {
                    $query->whereRaw("REPLACE(REPLACE(REPLACE(REPLACE(phone, '(', ''), ')', ''), '-', ''), ' ', '') = ?", [$digits])
                        ->orWhereRaw("REPLACE(REPLACE(REPLACE(REPLACE(cellphone, '(', ''), ')', ''), '-', ''), ' ', '') = ?", [$digits]);
                })
                ->orderBy('id')
                ->first();

            if ($patient) {
                return $patient;
            }
        }

        $normalizedName = Str::of($name)->lower()->squish()->toString();
        $patient = Patient::query()
            ->whereHas('companies', fn ($query) => $query->whereKey($companyId))
            ->get()
            ->first(fn (Patient $patient) => Str::of((string) $patient->full_name)->lower()->squish()->toString() === $normalizedName);

        if ($patient) {
            return $patient;
        }

        $patient = Patient::create([
            'full_name' => $name,
            'phone' => $formattedPhone ?: null,
            'cellphone' => $formattedPhone ?: null,
            'whatsapp' => $formattedPhone !== '',
            'whatsapp_reminders_enabled' => $formattedPhone !== '',
            'status' => 'ativo',
            'created_by_name' => 'Agendamento online',
        ]);
        $patient->companies()->syncWithoutDetaching([$companyId]);

        return $patient;
    }

    private function formatBrazilPhone(string $value): string
    {
        $digits = preg_replace('/\D+/', '', $value) ?: '';
        if (str_starts_with($digits, '55') && strlen($digits) > 11) {
            $digits = substr($digits, 2);
        }

        if (strlen($digits) === 11) {
            return sprintf('(%s) %s-%s', substr($digits, 0, 2), substr($digits, 2, 5), substr($digits, 7, 4));
        }

        if (strlen($digits) === 10) {
            return sprintf('(%s) %s-%s', substr($digits, 0, 2), substr($digits, 2, 4), substr($digits, 6, 4));
        }

        return '';
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

    private function bookingServices(Service $service): Collection
    {
        if ($service->is_package) {
            $items = $service->relationLoaded('packageItems')
                ? $service->packageItems
                : $service->packageItems()->get();

            if ($items->isNotEmpty()) {
                return $items->values();
            }
        }

        return collect([$service]);
    }

    private function publicBookingSequenceKey(string $token): string
    {
        return "public_booking_sequence_{$token}";
    }

    private function pendingBookingItems(Request $request, string $token): Collection
    {
        return collect($request->session()->get($this->publicBookingSequenceKey($token), []))
            ->map(function (array $item) {
                $item['scheduled_at'] = Carbon::parse($item['scheduled_at']);
                $item['ends_at'] = Carbon::parse($item['ends_at']);

                return $item;
            })
            ->values();
    }

    private function appendPendingBookingItems(Request $request, string $token, array $items): void
    {
        $pending = $this->pendingBookingItems($request, $token)
            ->merge($items)
            ->map(function (array $item) {
                $item['scheduled_at'] = $item['scheduled_at'] instanceof Carbon
                    ? $item['scheduled_at']->toIso8601String()
                    : (string) $item['scheduled_at'];
                $item['ends_at'] = $item['ends_at'] instanceof Carbon
                    ? $item['ends_at']->toIso8601String()
                    : (string) $item['ends_at'];

                return $item;
            })
            ->values()
            ->all();

        $request->session()->put($this->publicBookingSequenceKey($token), $pending);
    }

    private function clearPendingBookingItems(Request $request, string $token): void
    {
        $request->session()->forget($this->publicBookingSequenceKey($token));
    }

    private function lastBookingItemEndsAt(Collection $items): ?Carbon
    {
        $last = $items->last();

        return $last ? Carbon::parse($last['ends_at']) : null;
    }

    private function buildBookingItems(Service $service, Unit $unit, Collection $professionals, array $serviceProfessionalIds, Carbon $scheduledAt): array
    {
        $duration = (int) ($service->duration_minutes ?: 30);
        $items = [];

        foreach ($this->bookingServices($service)->values() as $position => $appointmentService) {
            $serviceDuration = (int) ($appointmentService->duration_minutes ?: $duration);
            $professionalId = (int) ($serviceProfessionalIds[$appointmentService->id] ?? collect($serviceProfessionalIds)->first());
            $professional = $professionals->firstWhere('id', $professionalId);

            $items[] = [
                'service_id' => (int) $appointmentService->id,
                'service_name' => (string) $appointmentService->name,
                'unit_id' => (int) $unit->id,
                'unit_name' => (string) $unit->name,
                'clinic_id' => (int) $unit->clinic_id,
                'professional_id' => $professionalId,
                'professional_name' => (string) ($professional?->display_name ?? 'Profissional'),
                'scheduled_at' => $scheduledAt->copy(),
                'ends_at' => $scheduledAt->copy()->addMinutes($serviceDuration),
                'duration_minutes' => $serviceDuration,
                'price_cents' => (int) ($appointmentService->price_cents ?? 0),
                'position' => $position,
            ];
        }

        return $items;
    }

    private function createAppointmentFromBookingItems(Patient $patient, Collection $items, string $channel, ?string $notes): Appointment
    {
        $items = $items->values();
        $first = $items->first();
        $last = $items->sortBy(fn ($item) => Carbon::parse($item['ends_at'])->timestamp)->last();
        $scheduledAt = Carbon::parse($first['scheduled_at']);
        $endsAt = Carbon::parse($last['ends_at']);
        $priceCents = (int) $items->sum('price_cents');
        $serviceNames = $items->pluck('service_name')->unique()->join(' + ');

        $appointment = Appointment::create([
            'clinic_id' => (int) $first['clinic_id'],
            'unit_id' => (int) $first['unit_id'],
            'professional_id' => (int) $first['professional_id'],
            'patient_id' => $patient->id,
            'service_id' => (int) $first['service_id'],
            'status' => 'agendado',
            'channel' => $channel,
            'scheduled_at' => $scheduledAt,
            'ends_at' => $endsAt,
            'duration_minutes' => max(1, $scheduledAt->diffInMinutes($endsAt)),
            'notes' => $notes,
            'price_cents' => $priceCents,
            'payment_status' => 'pending',
        ]);

        $sync = [];
        foreach ($items as $position => $item) {
            $sync[(int) $item['service_id']] = [
                'professional_id' => (int) $item['professional_id'],
                'duration_minutes' => (int) $item['duration_minutes'],
                'price_cents' => (int) $item['price_cents'],
                'scheduled_at' => Carbon::parse($item['scheduled_at']),
                'ends_at' => Carbon::parse($item['ends_at']),
                'status' => 'agendado',
                'commission_amount_cents' => 0,
                'position' => $position,
            ];
        }
        $appointment->services()->sync($sync);

        AccountReceivable::firstOrCreate(
            ['appointment_id' => $appointment->id],
            [
                'clinic_id' => $appointment->clinic_id,
                'unit_id' => $appointment->unit_id,
                'professional_id' => $appointment->professional_id,
                'patient_id' => $appointment->patient_id,
                'categoria_financeira_id' => $this->resolveReceivableCategoryId((int) $appointment->clinic_id),
                'descricao' => 'Atendimento ' . $serviceNames,
                'valor_total_cents' => $priceCents,
                'numero_parcelas' => 1,
                'numero_parcela' => 1,
                'valor_parcela_cents' => $priceCents,
                'data_emissao' => now()->toDateString(),
                'data_vencimento' => $appointment->scheduled_at->toDateString(),
                'status' => 'aberto',
            ]
        );

        return $appointment;
    }

    private function decodePackageSlotAssignments(string $value): array
    {
        if (! str_starts_with($value, 'pkg:')) {
            abort(422);
        }

        $decoded = json_decode(base64_decode(substr($value, 4), true) ?: '', true);
        abort_unless(is_array($decoded) && ! empty($decoded), 422);

        return collect($decoded)
            ->mapWithKeys(fn ($professionalId, $serviceId) => [(int) $serviceId => (int) $professionalId])
            ->all();
    }

    private function availableProfessionals(int $companyId, Service $service, ?Unit $unit): Collection
    {
        $serviceIds = $this->bookingServices($service)->pluck('id')->values();

        return Professional::query()
            ->where('active', true)
            ->where(function ($query) use ($companyId) {
                $query->where('company_id', $companyId)
                    ->orWhereHas('user.companies', fn ($companyQuery) => $companyQuery->whereKey($companyId));
            })
            ->whereHas('services', function ($query) use ($serviceIds) {
                $query->whereIn('services.id', $serviceIds)
                    ->where('professional_service.active', true);
            })
            ->when($unit, fn ($query) => $query->whereHas('units', fn ($unitQuery) => $unitQuery->whereKey($unit->id)))
            ->with('services')
            ->orderBy('display_name')
            ->get();
    }

    private function availableSlots(Service $service, Unit $unit, Collection $professionals, Carbon $date, ?Professional $selectedProfessional, ?Carbon $forcedStart = null): Collection
    {
        if ($service->is_package && $this->bookingServices($service)->count() > 1) {
            return $this->availablePackageSlots($service, $unit, $professionals, $date, $forcedStart);
        }

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
            ->where('unit_id', $unit->id)
            ->whereBetween('scheduled_at', [$dayStart, $dayEnd])
            ->whereNotIn('status', ['cancelado', 'cancelled'])
            ->where(function ($query) use ($professionalIds) {
                $query->whereIn('professional_id', $professionalIds)
                    ->orWhereHas('services', fn ($serviceQuery) => $serviceQuery->whereIn('appointment_service.professional_id', $professionalIds));
            })
            ->with('services')
            ->get();
        $blocks = ScheduleBlock::query()
            ->whereIn('professional_id', $professionalIds)
            ->where(function ($query) use ($unit) {
                $query->whereNull('unit_id')->orWhere('unit_id', $unit->id);
            })
            ->where('starts_at', '<=', $dayEnd)
            ->where('ends_at', '>=', $dayStart)
            ->get(['professional_id', 'starts_at', 'ends_at']);

        if ($forcedStart) {
            return $professionalIds
                ->map(function (int $professionalId) use ($professionals, $unit, $duration, $forcedStart, $appointments, $blocks) {
                    $professional = $professionals->firstWhere('id', $professionalId);
                    if (! $professional) {
                        return null;
                    }

                    $slotStart = $forcedStart->copy();
                    $slotEnd = $slotStart->copy()->addMinutes($duration);
                    if (
                        $this->slotIsAfterNow($slotStart)
                        && $this->scheduleAllows($professional->id, $unit->id, $slotStart, $slotEnd)
                        && ! $this->hasConflict($professional->id, $slotStart, $slotEnd, $appointments, $blocks)
                    ) {
                        return [
                            'professional' => $professional,
                            'scheduled_at' => $slotStart,
                            'ends_at' => $slotEnd,
                            'label' => $slotStart->format('H:i') . ' - ' . $professional->display_name,
                            'value' => $professional->id . '|' . $slotStart->toDateTimeString(),
                        ];
                    }

                    return null;
                })
                ->filter()
                ->sortBy(fn ($slot) => $slot['professional']->display_name)
                ->values();
        }

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

                        if ($this->slotIsAfterNow($slotStart) && ! $this->hasConflict($professional->id, $slotStart, $slotEnd, $appointments, $blocks)) {
                            $slots[] = [
                                'professional' => $professional,
                                'scheduled_at' => $slotStart,
                                'ends_at' => $slotEnd,
                                'label' => $slotStart->format('H:i') . ' - ' . $professional->display_name,
                                'value' => $professional->id . '|' . $slotStart->toDateTimeString(),
                            ];
                        }

                        $cursor->addMinutes($this->bookingSlotIntervalMinutes());
                    }
                }

                return $slots;
            })
            ->sortBy(fn ($slot) => $slot['scheduled_at']->timestamp . '-' . $slot['professional']->display_name)
            ->values();
    }

    private function availablePackageSlots(Service $service, Unit $unit, Collection $professionals, Carbon $date, ?Carbon $forcedStart = null): Collection
    {
        $services = $this->bookingServices($service);
        $duration = (int) ($service->duration_minutes ?: max(30, (int) $services->max('duration_minutes')));
        $professionalIds = $professionals->pluck('id')->values();

        if ($professionalIds->isEmpty()) {
            return collect();
        }

        $dayStart = $date->copy()->startOfDay();
        $dayEnd = $date->copy()->endOfDay();
        $appointments = Appointment::query()
            ->where('unit_id', $unit->id)
            ->whereBetween('scheduled_at', [$dayStart, $dayEnd])
            ->whereNotIn('status', ['cancelado', 'cancelled'])
            ->where(function ($query) use ($professionalIds) {
                $query->whereIn('professional_id', $professionalIds)
                    ->orWhereHas('services', fn ($serviceQuery) => $serviceQuery->whereIn('appointment_service.professional_id', $professionalIds));
            })
            ->with('services')
            ->get();
        $blocks = ScheduleBlock::query()
            ->whereIn('professional_id', $professionalIds)
            ->where(function ($query) use ($unit) {
                $query->whereNull('unit_id')->orWhere('unit_id', $unit->id);
            })
            ->where('starts_at', '<=', $dayEnd)
            ->where('ends_at', '>=', $dayStart)
            ->get(['professional_id', 'starts_at', 'ends_at']);

        if ($forcedStart) {
            $assignments = [];
            foreach ($services as $component) {
                $componentDuration = (int) ($component->duration_minutes ?: $duration);
                $slotStart = $forcedStart->copy();
                $slotEnd = $slotStart->copy()->addMinutes($componentDuration);
                $professional = $professionals->first(function (Professional $professional) use ($component, $unit, $slotStart, $slotEnd, $appointments, $blocks) {
                    return $professional->services->contains('id', $component->id)
                        && $this->scheduleAllows($professional->id, $unit->id, $slotStart, $slotEnd)
                        && ! $this->hasConflict($professional->id, $slotStart, $slotEnd, $appointments, $blocks);
                });

                if (! $professional) {
                    return collect();
                }

                $assignments[$component->id] = $professional->id;
            }

            $assignmentLabel = collect($assignments)
                ->map(fn ($professionalId, $serviceId) => optional($services->firstWhere('id', (int) $serviceId))->name . ': ' . optional($professionals->firstWhere('id', (int) $professionalId))->display_name)
                ->join(' | ');

            return collect([[
                'professional' => $professionals->firstWhere('id', (int) collect($assignments)->first()),
                'scheduled_at' => $forcedStart->copy(),
                'ends_at' => $forcedStart->copy()->addMinutes($duration),
                'label' => $assignmentLabel,
                'value' => 'pkg:' . base64_encode(json_encode($assignments)) . '|' . $forcedStart->toDateTimeString(),
            ]]);
        }

        $slots = [];
        $cursor = $date->copy()->startOfDay();
        while ($cursor->copy()->addMinutes($duration)->lessThanOrEqualTo($dayEnd)) {
            $slotStart = $cursor->copy();
            $assignments = [];

            if ($this->slotIsAfterNow($slotStart)) {
                foreach ($services as $component) {
                    $componentDuration = (int) ($component->duration_minutes ?: $duration);
                    $slotEnd = $slotStart->copy()->addMinutes($componentDuration);
                    $professional = $professionals->first(function (Professional $professional) use ($component, $unit, $slotStart, $slotEnd, $appointments, $blocks) {
                        return $professional->services->contains('id', $component->id)
                            && $this->scheduleAllows($professional->id, $unit->id, $slotStart, $slotEnd)
                            && ! $this->hasConflict($professional->id, $slotStart, $slotEnd, $appointments, $blocks);
                    });

                    if (! $professional) {
                        $assignments = [];
                        break;
                    }

                    $assignments[$component->id] = $professional->id;
                }
            }

            if (! empty($assignments)) {
                $assignmentLabel = collect($assignments)
                    ->map(fn ($professionalId, $serviceId) => optional($services->firstWhere('id', (int) $serviceId))->name . ': ' . optional($professionals->firstWhere('id', (int) $professionalId))->display_name)
                    ->join(' | ');
                $slots[] = [
                    'professional' => $professionals->firstWhere('id', (int) collect($assignments)->first()),
                    'scheduled_at' => $slotStart,
                    'ends_at' => $slotStart->copy()->addMinutes($duration),
                    'label' => $assignmentLabel,
                    'value' => 'pkg:' . base64_encode(json_encode($assignments)) . '|' . $slotStart->toDateTimeString(),
                ];
            }

            $cursor->addMinutes($this->bookingSlotIntervalMinutes());
        }

        return collect($slots)->values();
    }

    private function hasConflict(int $professionalId, Carbon $slotStart, Carbon $slotEnd, Collection $appointments, Collection $blocks): bool
    {
        $appointmentConflict = $appointments
            ->contains(function (Appointment $appointment) use ($professionalId, $slotStart, $slotEnd) {
                $services = $appointment->relationLoaded('services') ? $appointment->services : $appointment->services()->get();
                if ($services->isNotEmpty()) {
                    return $services->contains(function (Service $service) use ($professionalId, $slotStart, $slotEnd) {
                        if ((int) ($service->pivot->professional_id ?? 0) !== $professionalId) {
                            return false;
                        }

                        $start = $service->pivot->scheduled_at
                            ? $this->bookingDateTime($service->pivot->scheduled_at)
                            : null;
                        if (! $start) {
                            return false;
                        }

                        $end = $service->pivot->ends_at
                            ? $this->bookingDateTime($service->pivot->ends_at)
                            : $start->copy()->addMinutes((int) ($service->pivot->duration_minutes ?: 30));

                        return $start < $slotEnd && $end > $slotStart;
                    });
                }

                if ((int) $appointment->professional_id !== $professionalId) {
                    return false;
                }

                $start = $this->bookingDateTime($appointment->scheduled_at);
                $end = $appointment->ends_at
                    ? $this->bookingDateTime($appointment->ends_at)
                    : $start->copy()->addMinutes((int) ($appointment->duration_minutes ?: 30));

                return $start < $slotEnd && $end > $slotStart;
            });

        if ($appointmentConflict) {
            return true;
        }

        return $blocks
            ->where('professional_id', $professionalId)
            ->contains(fn (ScheduleBlock $block) => $this->bookingDateTime($block->starts_at) < $slotEnd && $this->bookingDateTime($block->ends_at) > $slotStart);
    }

    private function scheduleAllows(int $professionalId, int $unitId, Carbon $start, Carbon $end): bool
    {
        $schedulesByWeekday = Schedule::query()
            ->where('professional_id', $professionalId)
            ->where('unit_id', $unitId)
            ->where('is_active', true)
            ->get()
            ->groupBy('weekday');

        $weekday = $start->dayOfWeekIso;
        $startMinutes = ($start->hour * 60) + $start->minute;
        $endMinutes = ($end->hour * 60) + $end->minute;

        $daySchedules = $schedulesByWeekday->get($weekday, collect());
        if ($daySchedules->isEmpty() && $schedulesByWeekday->isEmpty()) {
            return true;
        }

        return $daySchedules->contains(function (Schedule $schedule) use ($startMinutes, $endMinutes) {
            [$scheduleStartHour, $scheduleStartMinute] = array_map('intval', explode(':', substr((string) $schedule->start_time, 0, 5)));
            [$scheduleEndHour, $scheduleEndMinute] = array_map('intval', explode(':', substr((string) $schedule->end_time, 0, 5)));
            $scheduleStart = ($scheduleStartHour * 60) + $scheduleStartMinute;
            $scheduleEnd = ($scheduleEndHour * 60) + $scheduleEndMinute;

            return $startMinutes >= $scheduleStart && $endMinutes <= $scheduleEnd;
        });
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
