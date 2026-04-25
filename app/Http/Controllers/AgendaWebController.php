<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\AccountReceivable;
use App\Models\FinancialCategory;
use App\Models\Clinic;
use App\Models\Patient;
use App\Models\Professional;
use App\Models\ScheduleBlock;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\Unit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AgendaWebController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        if ($request->user()?->is_platform_admin && ! $request->session()->get('active_company_id')) {
            return redirect()->route('admin.company-select');
        }

        $view = $request->string('view', 'day')->toString();
        if (! in_array($view, ['day', 'week', 'month'], true)) {
            $view = 'day';
        }

        $dateInput = $request->string('date', now()->toDateString())->toString();
        $date = Carbon::parse($dateInput);

        $selectedProfessionalId = $request->integer('professional_id') ?: null;
        $selectedUnitId = $request->integer('unit_id') ?: null;
        $selectedClinicId = null;

        $companyId = $request->session()->get('active_company_id');
        $clinicIds = $companyId ? Clinic::where('company_id', $companyId)->pluck('id') : collect();

        $user = $request->user();
        if ($user?->professional) {
            $canViewAll = $user->is_platform_admin
                || ($companyId && $user->hasCompanyPermission($companyId, 'agendamento.agendamentos.view'));
            if (! $canViewAll) {
                $selectedProfessionalId = $user->professional->id;
            }
        }

        if ($view === 'week') {
            $start = $date->copy()->startOfWeek(Carbon::MONDAY)->startOfDay();
            $end = $date->copy()->endOfWeek(Carbon::SUNDAY)->endOfDay();
        } elseif ($view === 'month') {
            $start = $date->copy()->startOfMonth()->startOfDay();
            $end = $date->copy()->endOfMonth()->endOfDay();
        } else {
            $start = $date->copy()->startOfDay();
            $end = $date->copy()->endOfDay();
        }

        $appointmentsQuery = Appointment::with(['patient', 'professional', 'service', 'unit', 'clinic'])
            ->whereBetween('scheduled_at', [$start, $end]);

        if ($companyId) {
            $appointmentsQuery->whereIn('clinic_id', $clinicIds);
        } elseif ($user->patient) {
            $appointmentsQuery->where('patient_id', $user->patient->id);
        }

        if ($selectedProfessionalId) {
            $appointmentsQuery->where('professional_id', $selectedProfessionalId);
        }
        if ($selectedUnitId) {
            $appointmentsQuery->where('unit_id', $selectedUnitId);
        }
        if ($selectedClinicId) {
            $appointmentsQuery->where('clinic_id', $selectedClinicId);
        }

        $appointments = $appointmentsQuery->orderBy('scheduled_at')->get();

        $blocksQuery = ScheduleBlock::with(['professional', 'unit'])
            ->whereBetween('starts_at', [$start, $end]);

        if ($selectedProfessionalId) {
            $blocksQuery->where('professional_id', $selectedProfessionalId);
        }
        if ($selectedUnitId) {
            $blocksQuery->where('unit_id', $selectedUnitId);
        }
        if ($selectedClinicId) {
            $unitIds = Unit::where('clinic_id', $selectedClinicId)->pluck('id');
            $blocksQuery->whereIn('unit_id', $unitIds);
        } elseif ($companyId) {
            $unitIds = Unit::whereIn('clinic_id', $clinicIds)->pluck('id');
            $blocksQuery->whereIn('unit_id', $unitIds);
        }

        $blocks = $blocksQuery->get();

        $professionals = Professional::query()
            ->when($companyId, function ($query) use ($companyId) {
                $query->where(function ($companyScoped) use ($companyId) {
                    $companyScoped
                        ->where('company_id', $companyId)
                        ->orWhereHas('user.companies', function ($companyQuery) use ($companyId) {
                            $companyQuery->where('companies.id', $companyId);
                        });
                });
            })
            ->with('user')
            ->orderBy('display_name')
            ->get();

        if ($selectedProfessionalId) {
            $professionals = $professionals->where('id', $selectedProfessionalId)->values();
        }

        $clinics = Clinic::query()
            ->when($companyId, function ($query) use ($clinicIds) {
                $query->whereIn('id', $clinicIds);
            })
            ->orderBy('name')
            ->get();

        $units = Unit::query()
            ->when($selectedClinicId, function ($query) use ($selectedClinicId) {
                $query->where('clinic_id', $selectedClinicId);
            })
            ->when(! $selectedClinicId && $companyId, function ($query) use ($clinicIds) {
                $query->whereIn('clinic_id', $clinicIds);
            })
            ->orderBy('name')
            ->get();

        $services = Service::query()
            ->when($companyId, function ($query) use ($companyId, $clinicIds) {
                $query->whereIn('clinic_id', $clinicIds);
            })
            ->orderBy('name')
            ->get();

        $patients = Patient::query()
            ->when($companyId, function ($query) use ($companyId) {
                $query->whereHas('companies', function ($companyQuery) use ($companyId) {
                    $companyQuery->where('companies.id', $companyId);
                });
            }, function ($query) use ($user) {
                if ($user->patient) {
                    $query->whereKey($user->patient->id);
                } else {
                    $query->whereRaw('1 = 0');
                }
            })
            ->orderBy('full_name')
            ->get();

        $slotMinutes = $view === 'day' ? 60 : 30;
        $defaultStart = '08:00';
        $defaultEnd = '19:00';
        $normalizeTime = static fn ($time, $fallback) => $time ? substr((string) $time, 0, 5) : $fallback;
        $scheduleClinic = null;
        if ($selectedClinicId) {
            $scheduleClinic = $clinics->firstWhere('id', $selectedClinicId);
        } elseif ($selectedUnitId) {
            $scheduleClinic = Unit::with('clinic')->find($selectedUnitId)?->clinic;
        } elseif ($clinicIds->count() === 1) {
            $scheduleClinic = $clinics->first();
        }
        if ($scheduleClinic) {
            $scheduleStart = $normalizeTime($scheduleClinic->schedule_start_time, $defaultStart);
            $scheduleEnd = $normalizeTime($scheduleClinic->schedule_end_time, $defaultEnd);
        } else {
            $scheduleStart = $clinics
                ->map(fn ($clinic) => $normalizeTime($clinic->schedule_start_time, $defaultStart))
                ->sort()
                ->first() ?? $defaultStart;
            $scheduleEnd = $clinics
                ->map(fn ($clinic) => $normalizeTime($clinic->schedule_end_time, $defaultEnd))
                ->sort()
                ->last() ?? $defaultEnd;
        }
        $dayStart = $date->copy()->setTimeFromTimeString($scheduleStart);
        $dayEnd = $date->copy()->setTimeFromTimeString($scheduleEnd);
        $totalMinutes = $dayStart->diffInMinutes($dayEnd);

        $timeSlots = [];
        $cursor = $dayStart->copy();
        while ($cursor < $dayEnd) {
            $timeSlots[] = [
                'label' => $cursor->format('H:i'),
                'is_hour' => $cursor->minute === 0,
            ];
            $cursor->addMinutes($slotMinutes);
        }

        $eventsByProfessional = [];
        $minWidthPercent = $totalMinutes > 0 ? ($slotMinutes / $totalMinutes) * 100 : 0;
        if ($view === 'day') {
            foreach ($appointments as $appointment) {
                $event = $this->mapAppointmentToEvent($appointment, $dayStart, $dayEnd, $totalMinutes, $minWidthPercent);
                if (! $event) {
                    continue;
                }
                $eventsByProfessional[$appointment->professional_id][] = $event;
            }

            foreach ($blocks as $block) {
                $event = $this->mapBlockToEvent($block, $dayStart, $dayEnd, $totalMinutes, $minWidthPercent);
                if (! $event) {
                    continue;
                }
                $eventsByProfessional[$block->professional_id][] = $event;
            }
        }

        $laneHeight = 110;
        $rowHeights = [];
        if ($view === 'day') {
            foreach ($eventsByProfessional as $professionalId => $events) {
                $laneData = $this->assignEventLanes($events);
                $eventsByProfessional[$professionalId] = $laneData['events'];
                $rowHeights[$professionalId] = max(170, ($laneData['laneCount'] * $laneHeight) + 20);
            }
        }

        $appointmentsByDay = $appointments->groupBy(function ($appointment) {
            return $appointment->scheduled_at->toDateString();
        });

        $weekDays = [];
        if ($view === 'week') {
            $cursor = $start->copy();
            while ($cursor <= $end) {
                $weekDays[] = $cursor->copy();
                $cursor->addDay();
            }
        }

        $calendarDays = [];
        if ($view === 'month') {
            $calendarStart = $start->copy()->startOfWeek(Carbon::MONDAY);
            $calendarEnd = $end->copy()->endOfWeek(Carbon::SUNDAY);
            $cursor = $calendarStart->copy();
            while ($cursor <= $calendarEnd) {
                $calendarDays[] = $cursor->copy();
                $cursor->addDay();
            }
        }

        $prevDate = $view === 'month'
            ? $date->copy()->subMonth()
            : ($view === 'week' ? $date->copy()->subWeek() : $date->copy()->subDay());
        $nextDate = $view === 'month'
            ? $date->copy()->addMonth()
            : ($view === 'week' ? $date->copy()->addWeek() : $date->copy()->addDay());

        $debugInfo = null;
        if ($request->boolean('debug')) {
            $debugInfo = [
                'company_id' => $companyId,
                'user_id' => $user->id,
                'user_is_patient' => (bool) $user->patient,
                'user_is_professional' => (bool) $user->professional,
                'selected_clinic_id' => $selectedClinicId,
                'selected_unit_id' => $selectedUnitId,
                'selected_professional_id' => $selectedProfessionalId,
                'clinic_ids' => $clinicIds->values()->all(),
                'range_start' => $start->toDateTimeString(),
                'range_end' => $end->toDateTimeString(),
                'appointment_ids' => $appointments->pluck('id')->values()->all(),
            ];
        }

        return view('sections.agenda', [
            'viewMode' => $view,
            'date' => $date,
            'prevDate' => $prevDate,
            'nextDate' => $nextDate,
            'professionals' => $professionals,
            'units' => $units,
            'clinics' => $clinics,
            'services' => $services,
            'patients' => $patients,
            'selectedProfessionalId' => $selectedProfessionalId,
            'selectedClinicId' => $selectedClinicId,
            'selectedUnitId' => $selectedUnitId,
            'appointments' => $appointments,
            'appointmentsByDay' => $appointmentsByDay,
            'weekDays' => $weekDays,
            'calendarDays' => $calendarDays,
            'timeSlots' => $timeSlots,
            'slotMinutes' => $slotMinutes,
            'scheduleStart' => $scheduleStart,
            'scheduleEnd' => $scheduleEnd,
            'appointmentsCount' => $appointments->count(),
            'eventsCount' => collect($eventsByProfessional)->flatten(1)->count(),
            'debugInfo' => $debugInfo,
            'eventsByProfessional' => $eventsByProfessional,
            'rowHeights' => $rowHeights ?? [],
            'laneHeight' => $laneHeight,
        ]);
    }

    public function store(Request $request)
    {
        $companyId = $request->session()->get('active_company_id');
        if (! $companyId) {
            abort(403);
        }

        $data = $request->validate([
            'unit_id' => ['required', 'integer', 'exists:units,id'],
            'professional_id' => ['required', 'integer', 'exists:professionals,id'],
            'patient_id' => ['required', 'integer', 'exists:patients,id'],
            'service_id' => ['required', 'integer', 'exists:services,id'],
            'channel' => ['required', 'string', 'max:20'],
            'scheduled_at' => ['required', 'date'],
            'duration_minutes' => ['nullable', 'integer', 'min:5', 'max:480'],
            'price' => $this->moneyRule(),
            'notes' => ['nullable', 'string'],
            'recurrence_type' => ['nullable', 'string', 'in:none,days,weekly,biweekly,monthly,semiannual'],
            'recurrence_interval_days' => ['nullable', 'required_if:recurrence_type,days', 'integer', 'min:1', 'max:365'],
            'recurrence_occurrences' => ['nullable', 'integer', 'min:1', 'max:120'],
        ]);

        $data['clinic_id'] = $this->resolveClinicIdFromUnit((int) $data['unit_id'], $companyId);

        $unitOk = Unit::whereKey($data['unit_id'])
            ->where('clinic_id', $data['clinic_id'])
            ->exists();
        if (! $unitOk) {
            return back()->withErrors(['unit_id' => 'Unidade invalida para a empresa logada.'])->withInput();
        }

        $service = Service::whereKey($data['service_id'])
            ->where('clinic_id', $data['clinic_id'])
            ->first();
        if (! $service) {
            return back()->withErrors(['service_id' => 'Servico invalido para a empresa logada.'])->withInput();
        }

        $professionalOk = Professional::whereKey($data['professional_id'])
            ->whereHas('user.companies', fn ($q) => $q->where('companies.id', $companyId))
            ->exists();
        if (! $professionalOk) {
            return back()->withErrors(['professional_id' => 'Profissional invalido para esta empresa.'])->withInput();
        }

        $patientOk = Patient::whereKey($data['patient_id'])
            ->whereHas('companies', fn ($q) => $q->where('companies.id', $companyId))
            ->exists();
        if (! $patientOk) {
            return back()->withErrors(['patient_id' => 'Cliente invalido para esta empresa.'])->withInput();
        }

        $scheduledAt = Carbon::parse($data['scheduled_at']);
        $duration = (int) ($data['duration_minutes'] ?? $service->duration_minutes);
        $priceCents = $data['price'] !== null
            ? $this->parsePriceToCents($data['price'])
            : $service->price_cents;

        $occurrenceDates = $this->buildRecurringDates($scheduledAt, $data);
        $schedulesByWeekday = Schedule::query()
            ->where('professional_id', $data['professional_id'])
            ->where('unit_id', $data['unit_id'])
            ->where('is_active', true)
            ->get()
            ->groupBy('weekday');

        foreach ($occurrenceDates as $occurrenceDate) {
            $occurrenceEndsAt = $occurrenceDate->copy()->addMinutes($duration);
            if (! $this->scheduleAllowsFromGrouped($schedulesByWeekday, $occurrenceDate, $occurrenceEndsAt)) {
                return back()->withErrors([
                    'scheduled_at' => 'Horario fora do atendimento do profissional em uma das recorrencias.',
                ])->withInput();
            }
        }

        DB::transaction(function () use ($data, $occurrenceDates, $duration, $service, $priceCents) {
            $recurrenceGroupId = count($occurrenceDates) > 1 ? (string) Str::uuid() : null;

            foreach ($occurrenceDates as $index => $occurrenceDate) {
                $endsAt = $occurrenceDate->copy()->addMinutes($duration);

                $appointment = Appointment::create([
                    'clinic_id' => $data['clinic_id'],
                    'unit_id' => $data['unit_id'],
                    'professional_id' => $data['professional_id'],
                    'patient_id' => $data['patient_id'],
                    'service_id' => $data['service_id'],
                    'status' => 'agendado',
                    'channel' => $data['channel'],
                    'scheduled_at' => $occurrenceDate,
                    'ends_at' => $endsAt,
                    'duration_minutes' => $duration,
                    'notes' => $data['notes'] ?? null,
                    'recurrence_group_id' => $recurrenceGroupId,
                    'recurrence_index' => $recurrenceGroupId ? (int) $index : null,
                    'price_cents' => $priceCents,
                    'payment_status' => 'pending',
                ]);

                AccountReceivable::firstOrCreate(
                    ['appointment_id' => $appointment->id],
                    [
                        'clinic_id' => $appointment->clinic_id,
                        'unit_id' => $appointment->unit_id,
                        'professional_id' => $appointment->professional_id,
                        'patient_id' => $appointment->patient_id,
                        'categoria_financeira_id' => $this->resolveReceivableCategoryId($appointment->clinic_id),
                        'descricao' => 'Atendimento ' . ($appointment->service?->name ?? ''),
                        'valor_total_cents' => $priceCents,
                        'numero_parcelas' => 1,
                        'numero_parcela' => 1,
                        'valor_parcela_cents' => $priceCents,
                        'data_emissao' => now()->toDateString(),
                        'data_vencimento' => $appointment->scheduled_at->toDateString(),
                        'status' => 'aberto',
                    ]
                );
            }
        });

        $count = count($occurrenceDates);
        $message = $count > 1
            ? "Agendamentos criados ({$count} ocorrencias)."
            : 'Agendamento criado.';

        return redirect()->route('agenda.index')->with('status', $message);
    }

    /**
     * @return array<int, Carbon>
     */
    private function buildRecurringDates(Carbon $baseDate, array $data): array
    {
        $type = $data['recurrence_type'] ?? 'none';
        if (! in_array($type, ['none', 'days', 'weekly', 'biweekly', 'monthly', 'semiannual'], true)) {
            $type = 'none';
        }

        $occurrences = (int) ($data['recurrence_occurrences'] ?? 1);
        $occurrences = max(1, min(120, $occurrences));

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

    protected function mapAppointmentToEvent(
        Appointment $appointment,
        Carbon $dayStart,
        Carbon $dayEnd,
        int $totalMinutes,
        float $minWidthPercent
    ): ?array
    {
        $start = $appointment->scheduled_at->copy();
        $end = $appointment->ends_at
            ? $appointment->ends_at->copy()
            : $start->copy()->addMinutes($appointment->service?->duration_minutes ?? 30);

        if ($end <= $dayStart || $start >= $dayEnd) {
            return null;
        }

        $start = $start->lessThan($dayStart) ? $dayStart->copy() : $start;
        $end = $end->greaterThan($dayEnd) ? $dayEnd->copy() : $end;

        $offsetMinutes = max(0, $dayStart->diffInMinutes($start, false));
        $durationMinutes = max(15, $start->diffInMinutes($end));
        $startMinute = $offsetMinutes;
        $endMinute = $offsetMinutes + $durationMinutes;

        $status = $this->normalizeStatus($appointment->status ?? 'agendado');
        $isCancelled = $status === 'cancelado';
        $isConfirmed = in_array($status, ['confirmado', 'atendido', 'concluido'], true);
        if ($isCancelled) {
            $statusClass = 'bg-error-50 border-error-200 text-error-800';
        } elseif ($isConfirmed) {
            $statusClass = 'bg-success-50 border-success-200 text-success-800';
        } elseif (in_array($appointment->channel, ['whatsapp', 'teleconsulta'], true)) {
            $statusClass = 'bg-blue-light-50 border-blue-light-200 text-blue-light-900';
        } else {
            $statusClass = 'bg-warning-50 border-warning-200 text-warning-900';
        }

        $leftPercent = ($offsetMinutes / $totalMinutes) * 100;
        $widthPercent = ($durationMinutes / $totalMinutes) * 100;
        $widthPercent = max($widthPercent, $minWidthPercent);
        if ($leftPercent + $widthPercent > 100) {
            $widthPercent = max(0, 100 - $leftPercent);
        }

        return [
            'type' => 'appointment',
            'id' => $appointment->id,
            'channel' => $appointment->channel,
            'title' => $appointment->patient?->full_name ?? 'Cliente',
            'subtitle' => $appointment->service?->name ?? 'Atendimento',
            'professional' => $appointment->professional?->display_name,
            'time' => $start->format('H:i').' - '.$end->format('H:i'),
            'left' => $leftPercent,
            'width' => $widthPercent,
            'start_minute' => $startMinute,
            'end_minute' => $endMinute,
            'status_class' => $statusClass,
            'edit_url' => route('appointments.edit', $appointment),
        ];
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

    protected function mapBlockToEvent(
        ScheduleBlock $block,
        Carbon $dayStart,
        Carbon $dayEnd,
        int $totalMinutes,
        float $minWidthPercent
    ): ?array
    {
        $start = $block->starts_at->copy();
        $end = $block->ends_at->copy();

        if ($end <= $dayStart || $start >= $dayEnd) {
            return null;
        }

        $start = $start->lessThan($dayStart) ? $dayStart->copy() : $start;
        $end = $end->greaterThan($dayEnd) ? $dayEnd->copy() : $end;

        $offsetMinutes = max(0, $dayStart->diffInMinutes($start, false));
        $durationMinutes = max(15, $start->diffInMinutes($end));
        $startMinute = $offsetMinutes;
        $endMinute = $offsetMinutes + $durationMinutes;

        $leftPercent = ($offsetMinutes / $totalMinutes) * 100;
        $widthPercent = ($durationMinutes / $totalMinutes) * 100;
        $widthPercent = max($widthPercent, $minWidthPercent);
        if ($leftPercent + $widthPercent > 100) {
            $widthPercent = max(0, 100 - $leftPercent);
        }

        return [
            'type' => 'block',
            'title' => $block->reason ?: 'Bloqueio',
            'time' => $start->format('H:i').' - '.$end->format('H:i'),
            'left' => $leftPercent,
            'width' => $widthPercent,
            'start_minute' => $startMinute,
            'end_minute' => $endMinute,
        ];
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

    private function moneyRule(bool $required = false): array
    {
        $rules = ['regex:/^\\d{1,3}(\\.\\d{3})*(,\\d{2})?$|^\\d+([.,]\\d{1,2})?$/'];
        array_unshift($rules, $required ? 'required' : 'nullable');

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

    protected function assignEventLanes(array $events): array
    {
        usort($events, function ($a, $b) {
            return ($a['start_minute'] ?? 0) <=> ($b['start_minute'] ?? 0);
        });

        $laneEnds = [];
        foreach ($events as &$event) {
            $assignedLane = null;
            $start = $event['start_minute'] ?? 0;
            $end = $event['end_minute'] ?? $start;

            foreach ($laneEnds as $laneIndex => $laneEnd) {
                if ($start >= $laneEnd) {
                    $assignedLane = $laneIndex;
                    $laneEnds[$laneIndex] = $end;
                    break;
                }
            }

            if ($assignedLane === null) {
                $assignedLane = count($laneEnds);
                $laneEnds[] = $end;
            }

            $event['lane'] = $assignedLane;
        }
        unset($event);

        $laneCount = max(1, count($laneEnds));
        foreach ($events as &$event) {
            $event['lane_count'] = $laneCount;
        }
        unset($event);

        return [
            'events' => $events,
            'laneCount' => $laneCount,
        ];
    }
}
