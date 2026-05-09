<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\AccountPayable;
use App\Models\AccountReceivable;
use App\Models\CashFlowEntry;
use App\Models\FinancialCategory;
use App\Models\Clinic;
use App\Models\Patient;
use App\Models\Professional;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\Unit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AppointmentWebController extends Controller
{
    public function index(Request $request): View
    {
        $companyId = $request->session()->get('active_company_id');
        if (! $companyId) {
            abort(403);
        }

        $user = $request->user();
        $query = Appointment::with(['clinic', 'unit', 'professional', 'patient', 'service', 'services']);

        $clinicIds = Clinic::where('company_id', $companyId)->pluck('id');
        $query->whereIn('clinic_id', $clinicIds);

        if ($user->patient && ! $companyId) {
            $query->where('patient_id', $user->patient->id);
        }

        if ($user->professional) {
            $canViewAll = $user->is_platform_admin
                || $user->hasCompanyPermission($companyId, 'agendamento.agendamentos.view');
            if (! $canViewAll) {
                $query->where('professional_id', $user->professional->id);
            }
        }

        $filters = [
            'date' => $request->string('date')->toString(),
            'unit_id' => $request->integer('unit_id') ?: null,
            'professional_id' => $request->integer('professional_id') ?: null,
            'patient_id' => $request->integer('patient_id') ?: null,
            'status' => $request->string('status', 'agendado')->toString(),
            'channel' => $request->string('channel')->toString(),
            'search' => $request->string('search')->toString(),
            'order_by' => $request->string('order_by', 'date_asc')->toString(),
        ];

        if ($filters['date']) {
            $query->whereDate('scheduled_at', $filters['date']);
        }
        if ($filters['unit_id']) {
            $query->where('unit_id', $filters['unit_id']);
        }
        if ($filters['professional_id']) {
            $query->where('professional_id', $filters['professional_id']);
        }
        if ($filters['patient_id']) {
            $query->where('patient_id', $filters['patient_id']);
        }
        if ($filters['status']) {
            $query->whereIn('status', $this->statusVariants($filters['status']));
        }
        if ($filters['channel']) {
            $query->whereIn('channel', $this->channelVariants($filters['channel']));
        }
        if ($filters['search']) {
            $search = $filters['search'];
            $query->where(function ($builder) use ($search) {
                $builder->whereHas('patient', function ($patientQuery) use ($search) {
                    $patientQuery->where('full_name', 'like', "%{$search}%");
                })->orWhereHas('professional', function ($professionalQuery) use ($search) {
                    $professionalQuery->where('display_name', 'like', "%{$search}%");
                })->orWhereHas('service', function ($serviceQuery) use ($search) {
                    $serviceQuery->where('name', 'like', "%{$search}%");
                });
            });
        }

        $allowedOrderBy = ['date_desc', 'date_asc'];
        if (! in_array($filters['order_by'], $allowedOrderBy, true)) {
            $filters['order_by'] = 'date_asc';
        }

        if ($filters['order_by'] === 'date_asc') {
            $query->orderBy('appointments.scheduled_at');
        } else {
            $query->orderByDesc('appointments.scheduled_at');
        }
        $query->orderByDesc('appointments.id');

        $perPage = $request->string('per_page', '10')->toString();
        if (! in_array($perPage, ['10', '20', '50', 'all'], true)) {
            $perPage = '10';
        }

        $appointments = $perPage === 'all'
            ? $query->get()
            : $query->paginate((int) $perPage)->withQueryString();

        $appointmentsCollection = $perPage === 'all'
            ? $appointments
            : collect($appointments->items());
        $recurrenceMeta = $this->buildRecurrenceMeta($appointmentsCollection);

        $clinicIds = Clinic::where('company_id', $companyId)->pluck('id');

        return view('appointments.index', [
            'appointments' => $appointments,
            'clinics' => Clinic::whereIn('id', $clinicIds)->orderBy('name')->get(),
            'units' => Unit::whereIn('clinic_id', $clinicIds)->orderBy('name')->get(),
            'professionals' => Professional::query()
                ->with('services')
                ->where(fn ($query) => $this->scopeProfessionalCompany($query, $companyId))
                ->orderBy('display_name')
                ->get(),
            'patients' => Patient::query()
                ->whereHas('companies', fn ($q) => $q->where('companies.id', $companyId))
                ->orderBy('full_name')
                ->get(),
            'filters' => $filters,
            'perPage' => $perPage,
            'recurrenceMeta' => $recurrenceMeta,
        ]);
    }

    public function create(Request $request): View
    {
        $companyId = $request->session()->get('active_company_id');
        if (! $companyId) {
            abort(403);
        }

        $clinicIds = Clinic::where('company_id', $companyId)->pluck('id');

        return view('appointments.create', [
            'appointment' => new Appointment(),
            'clinics' => Clinic::whereIn('id', $clinicIds)->orderBy('name')->get(),
            'units' => Unit::whereIn('clinic_id', $clinicIds)->orderBy('name')->get(),
            'professionals' => Professional::query()
                ->with('services')
                ->where(fn ($query) => $this->scopeProfessionalCompany($query, $companyId))
                ->orderBy('display_name')
                ->get(),
            'patients' => Patient::query()
                ->whereHas('companies', fn ($q) => $q->where('companies.id', $companyId))
                ->orderBy('full_name')
                ->get(),
            'services' => Service::whereIn('clinic_id', $clinicIds)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $companyId = $request->session()->get('active_company_id');
        if (! $companyId) {
            abort(403);
        }

        $data = $this->validateAppointment($request, true);
        $data['clinic_id'] = $this->resolveClinicIdFromUnit((int) $data['unit_id'], $companyId);
        $selectedServices = $this->resolveSelectedServices($data, (int) $data['clinic_id']);
        $primaryService = $selectedServices->first();
        $data['service_id'] = $primaryService->id;
        $data['professional_id'] = $this->resolvePrimaryProfessionalId($data, $selectedServices);

        $this->validateClinicRelations($data, $companyId);
        $this->validateServiceProfessionals($data, $selectedServices, $companyId);

        $scheduledAt = Carbon::parse($data['scheduled_at']);
        $durationMinutes = (int) ($data['duration_minutes'] ?? $selectedServices->sum('duration_minutes'));
        $priceCents = $this->resolvePriceCents($data, (int) $selectedServices->sum('price_cents'));
        $status = $this->normalizeStatus($data['status']);
        $occurrenceDates = $this->buildRecurringDates($scheduledAt, $data);
        $schedulesByWeekday = Schedule::query()
            ->where('professional_id', $data['professional_id'])
            ->where('unit_id', $data['unit_id'])
            ->where('is_active', true)
            ->get()
            ->groupBy('weekday');

        foreach ($occurrenceDates as $occurrenceDate) {
            $occurrenceEndsAt = $occurrenceDate->copy()->addMinutes($durationMinutes);
            if (! $this->scheduleAllowsFromGrouped($schedulesByWeekday, $occurrenceDate, $occurrenceEndsAt)) {
                return back()->withErrors([
                    'scheduled_at' => 'Horario fora do atendimento do profissional em uma das recorrencias.',
                ])->withInput();
            }
        }

        DB::transaction(function () use ($data, $occurrenceDates, $durationMinutes, $priceCents, $status, $selectedServices) {
            $recurrenceGroupId = count($occurrenceDates) > 1 ? (string) Str::uuid() : null;

            foreach ($occurrenceDates as $index => $occurrenceDate) {
                $endsAt = $occurrenceDate->copy()->addMinutes($durationMinutes);

                $appointment = Appointment::create([
                    'clinic_id' => $data['clinic_id'],
                    'unit_id' => $data['unit_id'],
                    'professional_id' => $data['professional_id'],
                    'patient_id' => $data['patient_id'],
                    'service_id' => $data['service_id'],
                    'status' => $status,
                    'channel' => $data['channel'],
                    'scheduled_at' => $occurrenceDate,
                    'ends_at' => $endsAt,
                    'duration_minutes' => $durationMinutes,
                    'is_first_visit' => (bool) ($data['is_first_visit'] ?? false),
                    'notes' => $data['notes'] ?? null,
                    'recurrence_group_id' => $recurrenceGroupId,
                    'recurrence_index' => $recurrenceGroupId ? (int) $index : null,
                    'price_cents' => $priceCents,
                    'payment_status' => $data['payment_status'] ?? 'pending',
                    'cancelled_at' => $status === 'cancelado' ? now() : null,
                    'cancellation_reason' => $data['cancellation_reason'] ?? null,
                ]);
                $this->syncAppointmentServices($appointment, $selectedServices, $data, $occurrenceDate, $status);

                $receivable = AccountReceivable::firstOrCreate(
                    ['appointment_id' => $appointment->id],
                    [
                        'clinic_id' => $appointment->clinic_id,
                        'unit_id' => $appointment->unit_id,
                        'professional_id' => $appointment->professional_id,
                        'patient_id' => $appointment->patient_id,
                        'categoria_financeira_id' => $this->resolveReceivableCategoryId($appointment->clinic_id),
                        'descricao' => 'Atendimento ' . $appointment->serviceNames(),
                        'valor_total_cents' => $priceCents,
                        'numero_parcelas' => 1,
                        'numero_parcela' => 1,
                        'valor_parcela_cents' => $priceCents,
                        'data_emissao' => now()->toDateString(),
                        'data_vencimento' => $appointment->scheduled_at->toDateString(),
                        'status' => 'aberto',
                    ]
                );

                $this->syncReceivablePayment($appointment, $receivable, $data['payment_status'] ?? null, $data['forma_pagamento'] ?? null);
            }
        });

        $count = count($occurrenceDates);
        $message = $count > 1
            ? "Agendamentos criados ({$count} ocorrencias)."
            : 'Agendamento criado.';

        return redirect()->route('appointments.index')->with('status', $message);
    }

    public function edit(Request $request, Appointment $appointment): View
    {
        $companyId = $request->session()->get('active_company_id');
        if (! $companyId || $appointment->clinic?->company_id !== $companyId) {
            abort(403);
        }

        $clinicIds = Clinic::where('company_id', $companyId)->pluck('id');

        return view('appointments.edit', [
            'appointment' => $appointment->load(['clinic', 'unit', 'professional', 'patient', 'service', 'services', 'receivable']),
            'clinics' => Clinic::whereIn('id', $clinicIds)->orderBy('name')->get(),
            'units' => Unit::whereIn('clinic_id', $clinicIds)->orderBy('name')->get(),
            'professionals' => Professional::query()
                ->with('services')
                ->where(fn ($query) => $this->scopeProfessionalCompany($query, $companyId))
                ->orderBy('display_name')
                ->get(),
            'patients' => Patient::query()
                ->whereHas('companies', fn ($q) => $q->where('companies.id', $companyId))
                ->orderBy('full_name')
                ->get(),
            'services' => Service::whereIn('clinic_id', $clinicIds)->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Appointment $appointment): RedirectResponse
    {
        $companyId = $request->session()->get('active_company_id');
        if (! $companyId || $appointment->clinic?->company_id !== $companyId) {
            abort(403);
        }

        $data = $this->validateAppointment($request, false);
        $data['clinic_id'] = $this->resolveClinicIdFromUnit((int) $data['unit_id'], $companyId);
        $selectedServices = $this->resolveSelectedServices($data, (int) $data['clinic_id']);
        $primaryService = $selectedServices->first();
        $data['service_id'] = $primaryService->id;
        $data['professional_id'] = $this->resolvePrimaryProfessionalId($data, $selectedServices);

        $this->validateClinicRelations($data, $companyId);
        $this->validateServiceProfessionals($data, $selectedServices, $companyId);

        $scheduledAt = Carbon::parse($data['scheduled_at']);
        $duration = (int) ($data['duration_minutes'] ?? $selectedServices->sum('duration_minutes'));
        $endsAt = $scheduledAt->copy()->addMinutes($duration);
        $status = $this->normalizeStatus($data['status']);
        $priceCents = $this->resolvePriceCents($data, (int) $selectedServices->sum('price_cents'));

        if (! $this->scheduleAllows($data['professional_id'], $data['unit_id'], $scheduledAt, $endsAt)) {
            return back()->withErrors([
                'scheduled_at' => 'Horario fora do atendimento do profissional.',
            ])->withInput();
        }

        DB::transaction(function () use ($appointment, $data, $status, $scheduledAt, $endsAt, $selectedServices, $duration, $priceCents) {
            $appointment->update([
                'clinic_id' => $data['clinic_id'],
                'unit_id' => $data['unit_id'],
                'professional_id' => $data['professional_id'],
                'patient_id' => $data['patient_id'],
                'service_id' => $data['service_id'],
                'status' => $status,
                'channel' => $data['channel'],
                'scheduled_at' => $scheduledAt,
                'ends_at' => $endsAt,
                'duration_minutes' => $duration,
                'is_first_visit' => (bool) ($data['is_first_visit'] ?? false),
                'notes' => $data['notes'] ?? null,
                'price_cents' => $priceCents,
                'payment_status' => $data['payment_status'] ?? $appointment->payment_status,
                'cancelled_at' => $status === 'cancelado' ? ($appointment->cancelled_at ?? now()) : null,
                'cancellation_reason' => $data['cancellation_reason'] ?? null,
            ]);
            $this->syncAppointmentServices($appointment, $selectedServices, $data, $scheduledAt, $status);

            $receivable = AccountReceivable::firstOrCreate(
                ['appointment_id' => $appointment->id],
                [
                    'clinic_id' => $appointment->clinic_id,
                    'unit_id' => $appointment->unit_id,
                    'professional_id' => $appointment->professional_id,
                    'patient_id' => $appointment->patient_id,
                    'categoria_financeira_id' => $this->resolveReceivableCategoryId($appointment->clinic_id),
                    'descricao' => 'Atendimento ' . $appointment->serviceNames(),
                    'valor_total_cents' => $priceCents,
                    'numero_parcelas' => 1,
                    'numero_parcela' => 1,
                    'valor_parcela_cents' => $priceCents,
                    'data_emissao' => now()->toDateString(),
                    'data_vencimento' => $appointment->scheduled_at->toDateString(),
                    'status' => 'aberto',
                ]
            );
            $receivable->fill([
                'clinic_id' => $appointment->clinic_id,
                'unit_id' => $appointment->unit_id,
                'professional_id' => $appointment->professional_id,
                'patient_id' => $appointment->patient_id,
                'descricao' => 'Atendimento ' . $appointment->serviceNames(),
                'valor_total_cents' => $priceCents,
                'valor_parcela_cents' => $priceCents,
                'data_vencimento' => $appointment->scheduled_at->toDateString(),
            ])->save();

            $this->syncReceivablePayment($appointment, $receivable, $data['payment_status'] ?? null, $data['forma_pagamento'] ?? null);

            $appointment->refresh()->load(['professional', 'service', 'services']);
            $paid = ($data['payment_status'] ?? $appointment->payment_status) === 'paid';
            if ($paid && $this->isCompletedStatus($status)) {
                $commission = $appointment->calculateCommissionCents();
                $appointment->fill([
                    'finished_at' => $appointment->finished_at ?? now(),
                    'commission_amount_cents' => $commission,
                    'salon_amount_cents' => max(($appointment->price_cents ?? 0) - $commission, 0),
                ])->save();

                $this->syncCommissionPayable($appointment, $commission);
            } else {
                $appointment->fill([
                    'commission_amount_cents' => 0,
                    'salon_amount_cents' => $appointment->price_cents ?? 0,
                ])->save();

                $this->removeOpenCommissionPayable($appointment);
            }
        });

        return redirect()->route('appointments.index')->with('status', 'Agendamento atualizado.');
    }

    public function destroy(Request $request, Appointment $appointment): RedirectResponse
    {
        $companyId = $request->session()->get('active_company_id');
        if (! $companyId || $appointment->clinic?->company_id !== $companyId) {
            abort(403);
        }

        $deleteFutureRecurrences = $request->boolean('delete_future_recurrences');
        $deletedCount = 1;

        if ($deleteFutureRecurrences && $appointment->recurrence_group_id) {
            $deletedCount = DB::transaction(function () use ($appointment) {
                $query = Appointment::query()
                    ->where('recurrence_group_id', $appointment->recurrence_group_id)
                    ->where('clinic_id', $appointment->clinic_id)
                    ->where(function ($q) use ($appointment) {
                        $q->where('scheduled_at', '>', $appointment->scheduled_at)
                            ->orWhere('id', $appointment->id);
                    });

                $count = (clone $query)->count();
                $query->delete();

                return $count;
            });
        } else {
            $appointment->delete();
        }

        $message = $deletedCount > 1
            ? "Agendamento e recorrencias futuras removidos ({$deletedCount})."
            : 'Agendamento removido.';

        return redirect()->route('appointments.index')->with('status', $message);
    }

    private function validateAppointment(Request $request, bool $isCreate): array
    {
        $rules = [
            'unit_id' => ['required', 'integer', 'exists:units,id'],
            'professional_id' => ['nullable', 'integer', 'exists:professionals,id'],
            'patient_id' => ['required', 'integer', 'exists:patients,id'],
            'service_id' => ['nullable', 'integer', 'exists:services,id', 'required_without:service_ids'],
            'service_ids' => ['nullable', 'array', 'min:1', 'required_without:service_id'],
            'service_ids.*' => ['integer', 'exists:services,id'],
            'service_professional_ids' => ['nullable', 'array'],
            'service_professional_ids.*' => ['nullable', 'integer', 'exists:professionals,id'],
            'status' => ['required', 'string', 'in:agendado,confirmado,atendido,concluido,cancelado,scheduled,confirmed,attended,done,cancelled'],
            'channel' => ['required', 'string', 'max:20'],
            'scheduled_at' => ['required', 'date'],
            'duration_minutes' => ['nullable', 'integer', 'min:5', 'max:480'],
            'is_first_visit' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
            'price' => $this->moneyRule('price_cents'),
            'price_cents' => ['required_without:price', 'nullable', 'integer', 'min:0'],
            'payment_status' => ['nullable', 'string', 'max:30'],
            'forma_pagamento' => ['nullable', 'string', 'max:20', 'required_if:payment_status,paid'],
            'cancellation_reason' => ['nullable', 'string', 'max:255'],
        ];

        if ($isCreate) {
            $rules['recurrence_type'] = ['nullable', 'string', 'in:none,days,weekly,weekly_days,biweekly,monthly,semiannual'];
            $rules['recurrence_interval_days'] = ['nullable', 'required_if:recurrence_type,days', 'integer', 'min:1', 'max:365'];
            $rules['recurrence_occurrences'] = ['nullable', 'integer', 'min:1', 'max:120'];
            $rules['recurrence_weekdays'] = ['nullable', 'required_if:recurrence_type,weekly_days', 'array', 'min:1'];
            $rules['recurrence_weekdays.*'] = ['integer', 'min:1', 'max:7'];
        }

        return $request->validate($rules);
    }

    /**
     * @return array<int, Carbon>
     */
    private function buildRecurringDates(Carbon $baseDate, array $data): array
    {
        $type = $data['recurrence_type'] ?? 'none';
        if (! in_array($type, ['none', 'days', 'weekly', 'weekly_days', 'biweekly', 'monthly', 'semiannual'], true)) {
            $type = 'none';
        }

        $occurrences = (int) ($data['recurrence_occurrences'] ?? 1);
        $occurrences = max(1, min(120, $occurrences));

        if ($type === 'weekly_days') {
            return $this->buildWeeklyDayRecurringDates($baseDate, $data, $occurrences);
        }

        $dates = [];
        for ($index = 0; $index < $occurrences; $index++) {
            $date = $baseDate->copy();

            if ($type === 'days') {
                $intervalDays = (int) ($data['recurrence_interval_days'] ?? 1);
                $intervalDays = max(1, min(365, $intervalDays));
                $date->addDays($intervalDays * $index);
            } elseif ($type === 'weekly') {
                $date->addWeeks($index);
            } elseif ($type === 'biweekly') {
                $date->addWeeks($index * 2);
            } elseif ($type === 'monthly') {
                $date->addMonthsNoOverflow($index);
            } elseif ($type === 'semiannual') {
                $date->addMonthsNoOverflow($index * 6);
            }

            $dates[] = $date;
        }

        return $dates;
    }

    /**
     * @return array<int, Carbon>
     */
    private function buildWeeklyDayRecurringDates(Carbon $baseDate, array $data, int $occurrences): array
    {
        $weekdays = collect($data['recurrence_weekdays'] ?? [])
            ->map(fn ($weekday) => (int) $weekday)
            ->filter(fn ($weekday) => $weekday >= 1 && $weekday <= 7)
            ->unique()
            ->sort()
            ->values();

        if ($weekdays->isEmpty()) {
            return [$baseDate->copy()];
        }

        $dates = [];
        $cursor = $baseDate->copy()->startOfDay();
        $time = [
            'hour' => $baseDate->hour,
            'minute' => $baseDate->minute,
            'second' => $baseDate->second,
        ];

        while (count($dates) < $occurrences) {
            if ($weekdays->contains($cursor->dayOfWeekIso) && $cursor->greaterThanOrEqualTo($baseDate->copy()->startOfDay())) {
                $dates[] = $cursor->copy()->setTime($time['hour'], $time['minute'], $time['second']);
            }

            $cursor->addDay();
        }

        return $dates;
    }

    private function normalizeStatus(?string $status): string
    {
        $status = strtolower(trim($status ?? 'agendado'));
        $map = [
            'scheduled' => 'agendado',
            'confirmed' => 'confirmado',
            'attended' => 'atendido',
            'done' => 'concluido',
            'cancelled' => 'cancelado',
        ];

        return $map[$status] ?? $status;
    }

    private function buildRecurrenceMeta(Collection $appointments): array
    {
        $meta = [];
        foreach ($appointments as $appointment) {
            $meta[$appointment->id] = [
                'is_recurring' => false,
                'has_future' => false,
                'future_count' => 0,
            ];
        }

        $groupIds = $appointments->pluck('recurrence_group_id')->filter()->unique()->values();
        if ($groupIds->isEmpty()) {
            return $meta;
        }

        $groupedAppointments = Appointment::query()
            ->select(['id', 'recurrence_group_id', 'scheduled_at'])
            ->whereIn('recurrence_group_id', $groupIds)
            ->orderBy('recurrence_group_id')
            ->orderBy('scheduled_at')
            ->orderBy('id')
            ->get()
            ->groupBy('recurrence_group_id');

        $futureByAppointmentId = [];
        foreach ($groupedAppointments as $items) {
            $total = $items->count();
            foreach ($items->values() as $index => $item) {
                $futureByAppointmentId[$item->id] = max(0, $total - $index - 1);
            }
        }

        foreach ($appointments as $appointment) {
            $futureCount = (int) ($futureByAppointmentId[$appointment->id] ?? 0);
            $isRecurring = ! empty($appointment->recurrence_group_id);

            $meta[$appointment->id] = [
                'is_recurring' => $isRecurring,
                'has_future' => $futureCount > 0,
                'future_count' => $futureCount,
            ];
        }

        return $meta;
    }

    private function statusVariants(string $status): array
    {
        $status = strtolower(trim($status));
        $map = [
            'scheduled' => 'agendado',
            'confirmed' => 'confirmado',
            'attended' => 'atendido',
            'done' => 'concluido',
            'cancelled' => 'cancelado',
        ];
        $reverseMap = array_flip($map);
        $normalized = $this->normalizeStatus($status);
        $variants = [$normalized];

        if (isset($map[$status])) {
            $variants[] = $status;
        } elseif (isset($reverseMap[$status])) {
            $variants[] = $reverseMap[$status];
        }

        return array_values(array_unique($variants));
    }

    private function channelVariants(string $channel): array
    {
        $channel = strtolower(trim($channel));

        if ($channel === 'home_care') {
            return ['home_care', 'whatsapp', 'teleconsulta'];
        }

        return [$channel];
    }

    private function resolvePriceCents(array $data, ?int $fallback = 0): int
    {
        if (array_key_exists('price', $data) && $data['price'] !== null && $data['price'] !== '') {
            return $this->parsePriceToCents($data['price']);
        }

        if (array_key_exists('price_cents', $data) && $data['price_cents'] !== null && $data['price_cents'] !== '') {
            return (int) $data['price_cents'];
        }

        return (int) $fallback;
    }

    private function resolveSelectedServices(array $data, int $clinicId): Collection
    {
        $serviceIds = collect($data['service_ids'] ?? [])
            ->filter()
            ->map(fn ($id) => (int) $id);

        if ($serviceIds->isEmpty() && ! empty($data['service_id'])) {
            $serviceIds = collect([(int) $data['service_id']]);
        }

        $serviceIds = $serviceIds->unique()->values();
        if ($serviceIds->isEmpty()) {
            abort(422, 'Selecione pelo menos um servico.');
        }

        $services = Service::where('clinic_id', $clinicId)
            ->whereIn('id', $serviceIds)
            ->get()
            ->sortBy(fn (Service $service) => $serviceIds->search((int) $service->id))
            ->values();

        if ($services->count() !== $serviceIds->count()) {
            abort(403);
        }

        return $services;
    }

    private function resolvePrimaryProfessionalId(array $data, Collection $services): int
    {
        $professionalMap = $data['service_professional_ids'] ?? [];
        foreach ($services as $service) {
            $professionalId = (int) ($professionalMap[$service->id] ?? 0);
            if ($professionalId > 0) {
                return $professionalId;
            }
        }

        $legacyProfessionalId = (int) ($data['professional_id'] ?? 0);
        if ($legacyProfessionalId > 0) {
            return $legacyProfessionalId;
        }

        throw ValidationException::withMessages([
            'service_professional_ids' => 'Selecione um profissional para cada servico marcado.',
        ]);
    }

    private function validateServiceProfessionals(array $data, Collection $services, int $companyId): void
    {
        $professionalMap = $data['service_professional_ids'] ?? [];

        foreach ($services as $service) {
            $professionalId = (int) ($professionalMap[$service->id] ?? $data['professional_id'] ?? 0);
            if ($professionalId <= 0) {
                throw ValidationException::withMessages([
                    'service_professional_ids' => 'Selecione um profissional para cada servico marcado.',
                ]);
            }

            $professional = Professional::whereKey($professionalId)
                ->where(fn ($query) => $this->scopeProfessionalCompany($query, $companyId))
                ->first();

            if (! $professional) {
                throw ValidationException::withMessages([
                    'service_professional_ids' => 'O profissional selecionado e invalido para esta empresa.',
                ]);
            }

            $canServe = $professional->services()
                ->where('services.id', $service->id)
                ->wherePivot('active', true)
                ->exists();

            if (! $canServe) {
                throw ValidationException::withMessages([
                    'service_professional_ids' => 'Um profissional selecionado nao atende o servico informado.',
                ]);
            }
        }
    }

    private function syncAppointmentServices(Appointment $appointment, Collection $services, array $data, Carbon $scheduledAt, string $status): void
    {
        $sync = [];
        $professionalMap = $data['service_professional_ids'] ?? [];
        foreach ($services->values() as $index => $service) {
            $duration = (int) $service->duration_minutes;
            $sync[$service->id] = [
                'professional_id' => (int) ($professionalMap[$service->id] ?? $appointment->professional_id),
                'duration_minutes' => $service->duration_minutes,
                'price_cents' => $service->price_cents,
                'scheduled_at' => $scheduledAt,
                'ends_at' => $scheduledAt->copy()->addMinutes($duration),
                'status' => $status,
                'commission_amount_cents' => 0,
                'position' => $index,
            ];
        }

        $appointment->services()->sync($sync);
    }

    private function parsePriceToCents(string $value): int
    {
        $raw = str_replace(['R$', 'r$', ' '], '', trim($value));
        if (str_contains($raw, ',')) {
            $raw = str_replace('.', '', $raw);
            $raw = str_replace(',', '.', $raw);
        }

        $amount = is_numeric($raw) ? (float) $raw : 0.0;

        return (int) round($amount * 100);
    }

    private function moneyRule(?string $requiredWithout = null): array
    {
        $rules = ['nullable', 'regex:/^\\d{1,3}(\\.\\d{3})*(,\\d{2})?$|^\\d+([.,]\\d{1,2})?$/'];
        if ($requiredWithout) {
            $rules[] = 'required_without:' . $requiredWithout;
        }

        return $rules;
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

        if ($category) {
            return $category->id;
        }

        return FinancialCategory::query()
            ->where('clinic_id', $clinicId)
            ->where('type', 'receber')
            ->orderBy('name')
            ->value('id');
    }

    private function scheduleAllows(int $professionalId, int $unitId, Carbon $start, Carbon $end): bool
    {
        $schedulesByWeekday = Schedule::query()
            ->where('professional_id', $professionalId)
            ->where('unit_id', $unitId)
            ->where('is_active', true)
            ->get()
            ->groupBy('weekday');

        return $this->scheduleAllowsFromGrouped($schedulesByWeekday, $start, $end);
    }

    private function scheduleAllowsFromGrouped($schedulesByWeekday, Carbon $start, Carbon $end): bool
    {
        $weekday = $start->dayOfWeekIso;
        $startMinutes = ($start->hour * 60) + $start->minute;
        $endMinutes = ($end->hour * 60) + $end->minute;

        if ($schedulesByWeekday->isEmpty()) {
            return true;
        }

        $schedules = $schedulesByWeekday->get($weekday);
        if (! $schedules || $schedules->isEmpty()) {
            return false;
        }

        foreach ($schedules as $schedule) {
            [$startHour, $startMinute] = array_map('intval', explode(':', $schedule->start_time));
            [$endHour, $endMinute] = array_map('intval', explode(':', $schedule->end_time));
            $scheduleStart = ($startHour * 60) + $startMinute;
            $scheduleEnd = ($endHour * 60) + $endMinute;

            if ($startMinutes >= $scheduleStart && $endMinutes <= $scheduleEnd) {
                return true;
            }
        }

        return false;
    }

    private function syncReceivablePayment(Appointment $appointment, AccountReceivable $receivable, ?string $paymentStatus, ?string $paymentMethod): void
    {
        if ($paymentStatus !== 'paid') {
            return;
        }

        $methodLabel = $this->paymentMethodLabel($paymentMethod);
        $baseDescription = 'Atendimento ' . $appointment->serviceNames();
        $descricao = $baseDescription;
        if ($methodLabel) {
            $descricao = $baseDescription . ' (Pago via ' . $methodLabel . ')';
        }

        $receivable->fill([
            'status' => 'pago',
            'forma_pagamento' => $paymentMethod ?: $receivable->forma_pagamento,
            'descricao' => $descricao,
        ]);

        if (! $receivable->pago_em) {
            $receivable->pago_em = now();
        }

        $receivable->save();

        $entry = CashFlowEntry::where('origem', 'conta_receber')
            ->where('origem_id', $receivable->id)
            ->first();

        if ($entry) {
            $entry->update([
                'descricao' => $receivable->descricao,
                'forma_pagamento' => $receivable->forma_pagamento,
            ]);
            return;
        }

        CashFlowEntry::create([
            'clinic_id' => $receivable->clinic_id,
            'unit_id' => $receivable->unit_id,
            'professional_id' => $receivable->professional_id ?? $appointment->professional_id,
            'categoria_financeira_id' => $receivable->categoria_financeira_id,
            'user_id' => auth()->id(),
            'tipo' => 'entrada',
            'origem' => 'conta_receber',
            'origem_id' => $receivable->id,
            'descricao' => $receivable->descricao,
            'valor_cents' => $receivable->valor_total_cents,
            'data_movimento' => $receivable->pago_em,
            'forma_pagamento' => $receivable->forma_pagamento,
        ]);
    }

    private function syncCommissionPayable(Appointment $appointment, int $commissionCents): void
    {
        if ($commissionCents <= 0 || ! $appointment->professional_id) {
            $this->removeOpenCommissionPayable($appointment);
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
                'observacoes' => 'Gerado automaticamente ao concluir o atendimento pago.',
                'origem' => 'commission',
            ]
        );
    }

    private function removeOpenCommissionPayable(Appointment $appointment): void
    {
        AccountPayable::where('appointment_id', $appointment->id)
            ->where('origem', 'commission')
            ->where('status', 'aberto')
            ->delete();
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

    private function isCompletedStatus(string $status): bool
    {
        return in_array($this->normalizeStatus($status), ['atendido', 'concluido'], true);
    }

    private function paymentMethodLabel(?string $paymentMethod): ?string
    {
        $map = [
            'pix' => 'Pix',
            'cartao' => 'Cartao',
            'dinheiro' => 'Dinheiro',
            'convenio' => 'Convenio',
            'boleto' => 'Boleto',
        ];

        $key = strtolower(trim($paymentMethod ?? ''));
        if (! $key) {
            return null;
        }

        return $map[$key] ?? ucfirst($key);
    }

    private function scopeProfessionalCompany($query, int $companyId): void
    {
        $query->where('company_id', $companyId)
            ->orWhereHas('user.companies', fn ($companyQuery) => $companyQuery->where('companies.id', $companyId));
    }

    private function validateClinicRelations(array $data, int $companyId): void
    {
        $clinic = Clinic::where('company_id', $companyId)->whereKey($data['clinic_id'])->first();
        if (! $clinic) {
            abort(403);
        }

        $unitOk = Unit::whereKey($data['unit_id'])
            ->where('clinic_id', $data['clinic_id'])
            ->exists();
        if (! $unitOk) {
            abort(403);
        }

        $serviceOk = Service::whereKey($data['service_id'])
            ->where('clinic_id', $data['clinic_id'])
            ->exists();
        if (! $serviceOk) {
            abort(403);
        }

        $professionalOk = Professional::whereKey($data['professional_id'])
            ->where(fn ($query) => $this->scopeProfessionalCompany($query, $companyId))
            ->exists();
        if (! $professionalOk) {
            abort(403);
        }

        $patientOk = Patient::whereKey($data['patient_id'])
            ->whereHas('companies', fn ($q) => $q->where('companies.id', $companyId))
            ->exists();
        if (! $patientOk) {
            abort(403);
        }
    }

    private function resolveClinicIdFromUnit(int $unitId, int $companyId): int
    {
        $unit = Unit::query()
            ->whereKey($unitId)
            ->whereHas('clinic', fn ($query) => $query->where('company_id', $companyId))
            ->first();

        if (! $unit) {
            abort(403);
        }

        return (int) $unit->clinic_id;
    }
}
