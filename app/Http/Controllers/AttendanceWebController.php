<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Clinic;
use App\Models\Patient;
use App\Models\Professional;
use App\Models\ScheduleBlock;
use App\Models\Service;
use App\Models\Unit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class AttendanceWebController extends AgendaWebController
{
    public function agenda(Request $request): View|RedirectResponse
    {
        $user = $request->user();
        $companyId = $request->session()->get('active_company_id');
        if ($user?->is_platform_admin && ! $request->session()->get('active_company_id')) {
            return redirect()->route('admin.company-select');
        }

        $canViewAll = $user?->is_platform_admin
            || ($companyId && $user?->hasCompanyPermission($companyId, 'atendimento.agenda.view'));
        if (! $canViewAll && ! $user?->professional) {
            return view('attendance/unavailable');
        }

        $view = $request->string('view', 'day')->toString();
        if (! in_array($view, ['day', 'week', 'month'], true)) {
            $view = 'day';
        }

        $dateInput = $request->string('date', now()->toDateString())->toString();
        $date = Carbon::parse($dateInput);

        $selectedProfessionalId = $canViewAll
            ? ($request->integer('professional_id') ?: null)
            : $user->professional->id;
        $selectedUnitId = $request->integer('unit_id') ?: null;
        $selectedClinicId = $request->integer('clinic_id') ?: null;

        $clinicIds = $companyId ? Clinic::where('company_id', $companyId)->pluck('id') : collect();
        if ($selectedClinicId && ! $clinicIds->contains($selectedClinicId)) {
            $selectedClinicId = null;
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

        $appointmentsQuery = Appointment::with(['patient', 'professional', 'service', 'services', 'unit', 'clinic'])
            ->whereBetween('scheduled_at', [$start, $end]);

        if ($companyId) {
            $appointmentsQuery->whereIn('clinic_id', $clinicIds);
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
            ->when($companyId, function ($query) use ($clinicIds) {
                $query->whereIn('clinic_id', $clinicIds);
            })
            ->orderBy('name')
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
                $event['edit_url'] = route('attendance.record.edit', $appointment);
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
            $gridStart = $start->copy()->startOfWeek(Carbon::MONDAY);
            $gridEnd = $end->copy()->endOfWeek(Carbon::SUNDAY);
            $cursor = $gridStart->copy();
            while ($cursor <= $gridEnd) {
                $calendarDays[] = $cursor->copy();
                $cursor->addDay();
            }
        }

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

        return view('sections/agenda', [
            'pageTitle' => 'Atendimento',
            'viewMode' => $view,
            'date' => $date,
            'prevDate' => $date->copy()->subDay(),
            'nextDate' => $date->copy()->addDay(),
            'selectedProfessionalId' => $selectedProfessionalId,
            'selectedUnitId' => $selectedUnitId,
            'selectedClinicId' => $selectedClinicId,
            'clinics' => $clinics,
            'units' => $units,
            'professionals' => $professionals,
            'services' => $services,
            'patients' => $patients,
            'timeSlots' => $timeSlots,
            'eventsByProfessional' => $eventsByProfessional,
            'rowHeights' => $rowHeights,
            'laneHeight' => $laneHeight,
            'appointmentsByDay' => $appointmentsByDay,
            'weekDays' => $weekDays,
            'calendarDays' => $calendarDays,
            'lockProfessionalFilter' => ! $canViewAll,
        ]);
    }

    public function index(Request $request): View
    {
        $user = $request->user();
        $companyId = $request->session()->get('active_company_id');
        $canViewAll = $user?->is_platform_admin
            || ($companyId && $user?->hasCompanyPermission($companyId, 'atendimento.atendimentos.view'));

        if (! $canViewAll && ! $user?->professional) {
            return view('attendance/unavailable');
        }

        $clinicIds = $companyId ? Clinic::where('company_id', $companyId)->pluck('id') : collect();
        $selectedClinicId = $request->integer('clinic_id') ?: null;
        if ($selectedClinicId && ! $clinicIds->contains($selectedClinicId)) {
            $selectedClinicId = null;
        }
        $selectedProfessionalId = $canViewAll
            ? ($request->integer('professional_id') ?: null)
            : $user->professional->id;

        $dateInput = $request->string('date', now()->toDateString())->toString();
        $date = Carbon::parse($dateInput);

        $query = Appointment::with(['clinic', 'unit', 'patient', 'professional', 'service', 'services'])
            ->whereDate('scheduled_at', $date->toDateString())
            ->orderBy('scheduled_at');

        if ($selectedProfessionalId) {
            $query->where('professional_id', $selectedProfessionalId);
        }
        if ($companyId) {
            $query->whereIn('clinic_id', $clinicIds);
        }
        if ($selectedClinicId) {
            $query->where('clinic_id', $selectedClinicId);
        }

        $appointments = $query->get();

        return view('attendance/index', [
            'appointments' => $appointments,
            'date' => $date,
            'clinics' => Clinic::when($companyId, fn ($q) => $q->whereIn('id', $clinicIds))
                ->orderBy('name')
                ->get(),
            'selectedClinicId' => $selectedClinicId,
            'professionals' => Professional::query()
                ->when($companyId, function ($query) use ($companyId) {
                    $query->where(function ($companyScoped) use ($companyId) {
                        $companyScoped
                            ->where('company_id', $companyId)
                            ->orWhereHas('user.companies', function ($companyQuery) use ($companyId) {
                                $companyQuery->where('companies.id', $companyId);
                            });
                    });
                })
                ->orderBy('display_name')
                ->get(),
            'selectedProfessionalId' => $selectedProfessionalId,
            'lockProfessionalFilter' => ! $canViewAll,
        ]);
    }
}
