<?php

namespace App\Http\Controllers;

use App\Models\AccountPayable;
use App\Models\AccountReceivable;
use App\Models\Appointment;
use App\Models\Clinic;
use App\Models\Patient;
use App\Models\Professional;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        if ($request->user()?->is_platform_admin && ! $request->session()->get('active_company_id')) {
            return redirect()->route('admin.company-select');
        }

        $period = $request->string('period', 'week')->toString();
        if (! in_array($period, ['day', 'week', 'month'], true)) {
            $period = 'week';
        }

        $now = now();
        if ($period === 'day') {
            $start = $now->copy()->startOfDay();
            $end = $now->copy()->endOfDay();
            $prevStart = $start->copy()->subDay();
            $prevEnd = $end->copy()->subDay();
        } elseif ($period === 'month') {
            $start = $now->copy()->startOfMonth();
            $end = $now->copy()->endOfMonth();
            $prevStart = $start->copy()->subMonth()->startOfMonth();
            $prevEnd = $end->copy()->subMonth()->endOfMonth();
        } else {
            $start = $now->copy()->startOfWeek(Carbon::MONDAY);
            $end = $now->copy()->endOfWeek(Carbon::SUNDAY);
            $prevStart = $start->copy()->subWeek();
            $prevEnd = $end->copy()->subWeek();
        }

        $companyId = $request->session()->get('active_company_id');
        $selectedClinicId = $request->integer('clinic_id') ?: null;
        $selectedUnitId = $request->integer('unit_id') ?: null;
        $selectedProfessionalId = $request->integer('professional_id') ?: null;

        $companyClinicIds = $companyId ? Clinic::where('company_id', $companyId)->pluck('id') : collect();
        if ($selectedClinicId && ! $companyClinicIds->contains($selectedClinicId)) {
            $selectedClinicId = null;
        }

        $clinicIds = $selectedClinicId ? collect([$selectedClinicId]) : $companyClinicIds;

        $unitsQuery = \App\Models\Unit::query()->when($clinicIds->isNotEmpty(), function ($query) use ($clinicIds) {
            $query->whereIn('clinic_id', $clinicIds);
        });
        $units = $unitsQuery->orderBy('name')->get();
        if ($selectedUnitId && ! $units->pluck('id')->contains($selectedUnitId)) {
            $selectedUnitId = null;
        }
        if (! $selectedUnitId && $units->count() === 1) {
            $selectedUnitId = (int) $units->first()->id;
        }

        $appointmentsBase = Appointment::query()
            ->when($clinicIds->isNotEmpty(), fn ($query) => $query->whereIn('clinic_id', $clinicIds), fn ($query) => $query->whereRaw('1 = 0'))
            ->when($selectedUnitId, fn ($query) => $query->where('unit_id', $selectedUnitId))
            ->when($selectedProfessionalId, fn ($query) => $query->where('professional_id', $selectedProfessionalId));

        $appointmentsPeriodCount = (clone $appointmentsBase)->whereBetween('scheduled_at', [$start, $end])->count();
        $appointmentsPrevCount = (clone $appointmentsBase)->whereBetween('scheduled_at', [$prevStart, $prevEnd])->count();

        $confirmedStatuses = ['confirmado', 'confirmed'];
        $attendedStatuses = ['atendido', 'attended', 'concluido', 'done'];
        $cancelledStatuses = ['cancelado', 'cancelled'];
        $noShowStatuses = ['faltou', 'no_show', 'no-show'];

        $confirmedCount = (clone $appointmentsBase)
            ->whereBetween('scheduled_at', [$start, $end])
            ->whereIn('status', $confirmedStatuses)
            ->count();
        $cancelledCount = (clone $appointmentsBase)
            ->whereBetween('scheduled_at', [$start, $end])
            ->whereIn('status', $cancelledStatuses)
            ->count();

        $patientsNewCount = 0;
        if ($companyId) {
            if ($selectedClinicId || $selectedUnitId || $selectedProfessionalId) {
                $patientsNewCount = Appointment::query()
                    ->when($clinicIds->isNotEmpty(), fn ($query) => $query->whereIn('clinic_id', $clinicIds))
                    ->when($selectedUnitId, fn ($query) => $query->where('unit_id', $selectedUnitId))
                    ->when($selectedProfessionalId, fn ($query) => $query->where('professional_id', $selectedProfessionalId))
                    ->whereBetween('scheduled_at', [$start, $end])
                    ->distinct('patient_id')
                    ->count('patient_id');
            } else {
                $patientsNewCount = Patient::whereHas('companies', function ($query) use ($companyId, $start, $end) {
                    $query->where('companies.id', $companyId)
                        ->whereBetween('company_patient.created_at', [$start, $end]);
                })->count();
            }
        }

        $appointmentsPeriod = (clone $appointmentsBase)
            ->whereBetween('scheduled_at', [$start, $end])
            ->get(['scheduled_at', 'ends_at', 'duration_minutes']);

        $scheduledMinutes = $appointmentsPeriod->sum(function ($appointment) {
            if ($appointment->duration_minutes) {
                return $appointment->duration_minutes;
            }
            if ($appointment->ends_at && $appointment->scheduled_at) {
                return $appointment->ends_at->diffInMinutes($appointment->scheduled_at);
            }
            return 0;
        });

        $clinics = Clinic::whereIn('id', $companyClinicIds)->get();
        $scheduleMinutes = $clinics->map(function ($clinic) {
            if (! $clinic->schedule_start_time || ! $clinic->schedule_end_time) {
                return null;
            }
            $startTime = Carbon::parse($clinic->schedule_start_time);
            $endTime = Carbon::parse($clinic->schedule_end_time);
            return max($endTime->diffInMinutes($startTime), 0);
        })->filter()->values();

        $dailyScheduleMinutes = $scheduleMinutes->isNotEmpty() ? (int) round($scheduleMinutes->avg()) : 0;
        $daysInPeriod = max($start->diffInDays($end) + 1, 1);
        $professionalsCount = $companyId
            ? Professional::whereHas('user.companies', fn ($query) => $query->where('companies.id', $companyId))->count()
            : 0;
        $availableMinutes = $dailyScheduleMinutes * $daysInPeriod * max($professionalsCount, 1);
        $occupancyPercent = $availableMinutes > 0 ? (int) round(($scheduledMinutes / $availableMinutes) * 100) : 0;
        $occupiedHours = round($scheduledMinutes / 60, 1);

        $receitaCents = AccountReceivable::query()
            ->when($clinicIds->isNotEmpty(), fn ($query) => $query->whereIn('clinic_id', $clinicIds), fn ($query) => $query->whereRaw('1 = 0'))
            ->when($selectedUnitId, fn ($query) => $query->where('unit_id', $selectedUnitId))
            ->when($selectedProfessionalId, fn ($query) => $query->where('professional_id', $selectedProfessionalId))
            ->whereBetween('data_vencimento', [$start, $end])
            ->where('status', 'pago')
            ->sum('valor_total_cents');

        $receberAbertoCents = AccountReceivable::query()
            ->when($clinicIds->isNotEmpty(), fn ($query) => $query->whereIn('clinic_id', $clinicIds), fn ($query) => $query->whereRaw('1 = 0'))
            ->when($selectedUnitId, fn ($query) => $query->where('unit_id', $selectedUnitId))
            ->when($selectedProfessionalId, fn ($query) => $query->where('professional_id', $selectedProfessionalId))
            ->whereIn('status', ['aberto', 'atrasado'])
            ->sum('valor_total_cents');
        $receberVencidas = AccountReceivable::query()
            ->when($clinicIds->isNotEmpty(), fn ($query) => $query->whereIn('clinic_id', $clinicIds), fn ($query) => $query->whereRaw('1 = 0'))
            ->when($selectedUnitId, fn ($query) => $query->where('unit_id', $selectedUnitId))
            ->when($selectedProfessionalId, fn ($query) => $query->where('professional_id', $selectedProfessionalId))
            ->whereIn('status', ['aberto', 'atrasado'])
            ->whereDate('data_vencimento', '<', $now->toDateString())
            ->count();

        $pagarAbertasCents = AccountPayable::query()
            ->when($clinicIds->isNotEmpty(), fn ($query) => $query->whereIn('clinic_id', $clinicIds), fn ($query) => $query->whereRaw('1 = 0'))
            ->when($selectedUnitId, fn ($query) => $query->where('unit_id', $selectedUnitId))
            ->whereIn('status', ['aberto', 'atrasado'])
            ->whereBetween('data_vencimento', [$now->toDateString(), $now->copy()->addDays(7)->toDateString()])
            ->sum('valor_cents');
        $pagarVencidas = AccountPayable::query()
            ->when($clinicIds->isNotEmpty(), fn ($query) => $query->whereIn('clinic_id', $clinicIds), fn ($query) => $query->whereRaw('1 = 0'))
            ->when($selectedUnitId, fn ($query) => $query->where('unit_id', $selectedUnitId))
            ->whereIn('status', ['aberto', 'atrasado'])
            ->whereDate('data_vencimento', '<', $now->toDateString())
            ->count();

        $todayStart = $now->copy()->startOfDay();
        $todayEnd = $now->copy()->endOfDay();
        $statusToday = [
            'confirmadas' => (clone $appointmentsBase)->whereBetween('scheduled_at', [$todayStart, $todayEnd])->whereIn('status', $confirmedStatuses)->count(),
            'atendidas' => (clone $appointmentsBase)->whereBetween('scheduled_at', [$todayStart, $todayEnd])->whereIn('status', $attendedStatuses)->count(),
            'canceladas' => (clone $appointmentsBase)->whereBetween('scheduled_at', [$todayStart, $todayEnd])->whereIn('status', $cancelledStatuses)->count(),
            'faltas' => (clone $appointmentsBase)->whereBetween('scheduled_at', [$todayStart, $todayEnd])->whereIn('status', $noShowStatuses)->count(),
        ];

        $todayAppointments = (clone $appointmentsBase)
            ->with(['patient', 'professional', 'service', 'services', 'unit'])
            ->whereBetween('scheduled_at', [$todayStart, $todayEnd])
            ->orderBy('scheduled_at')
            ->limit(40)
            ->get();

        $waitingCount = Appointment::query()
            ->when($clinicIds->isNotEmpty(), fn ($query) => $query->whereIn('clinic_id', $clinicIds), fn ($query) => $query->whereRaw('1 = 0'))
            ->when($selectedUnitId, fn ($query) => $query->where('unit_id', $selectedUnitId))
            ->when($selectedProfessionalId, fn ($query) => $query->where('professional_id', $selectedProfessionalId))
            ->where('status', 'waiting')
            ->count();

        $inProgressCount = Appointment::query()
            ->when($clinicIds->isNotEmpty(), fn ($query) => $query->whereIn('clinic_id', $clinicIds), fn ($query) => $query->whereRaw('1 = 0'))
            ->when($selectedUnitId, fn ($query) => $query->where('unit_id', $selectedUnitId))
            ->when($selectedProfessionalId, fn ($query) => $query->where('professional_id', $selectedProfessionalId))
            ->where('status', 'in_progress')
            ->count();

        $weekDays = collect(range(0, 6))
            ->map(fn ($offset) => $now->copy()->startOfWeek(Carbon::SUNDAY)->addDays($offset));

        $topProfessionals = (clone $appointmentsBase)
            ->whereBetween('scheduled_at', [$start, $end])
            ->selectRaw('professional_id, count(*) as total')
            ->whereNotNull('professional_id')
            ->groupBy('professional_id')
            ->orderByDesc('total')
            ->limit(4)
            ->get();

        $professionalIds = $topProfessionals->pluck('professional_id');
        $professionalsMap = Professional::whereIn('id', $professionalIds)->pluck('display_name', 'id');
        $maxProfessionalTotal = max($topProfessionals->max('total') ?? 0, 1);
        $professionalStats = $topProfessionals->map(function ($row) use ($professionalsMap, $maxProfessionalTotal) {
            $name = $professionalsMap[$row->professional_id] ?? 'Profissional';
            return [
                'name' => $name,
                'total' => (int) $row->total,
                'percent' => (int) round(($row->total / $maxProfessionalTotal) * 100),
            ];
        })->values();

        $periodLabels = [
            'day' => 'Hoje',
            'week' => 'Semana',
            'month' => 'Mes',
        ];

        $rangeLabel = $period === 'day'
            ? $start->format('d/m/Y')
            : sprintf('%s - %s', $start->format('d/m'), $end->format('d/m'));

        $chartStart = $now->copy()->subDays(29)->startOfDay();
        $chartDays = collect();
        for ($i = 0; $i < 30; $i++) {
            $chartDays->push($chartStart->copy()->addDays($i));
        }

        $appointmentsSeries = Appointment::query()
            ->when($clinicIds->isNotEmpty(), fn ($query) => $query->whereIn('clinic_id', $clinicIds), fn ($query) => $query->whereRaw('1 = 0'))
            ->when($selectedUnitId, fn ($query) => $query->where('unit_id', $selectedUnitId))
            ->when($selectedProfessionalId, fn ($query) => $query->where('professional_id', $selectedProfessionalId))
            ->whereBetween('scheduled_at', [$chartStart, $now->copy()->endOfDay()])
            ->selectRaw('date(scheduled_at) as day, count(*) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        $revenueSeries = AccountReceivable::query()
            ->when($clinicIds->isNotEmpty(), fn ($query) => $query->whereIn('clinic_id', $clinicIds), fn ($query) => $query->whereRaw('1 = 0'))
            ->when($selectedUnitId, fn ($query) => $query->where('unit_id', $selectedUnitId))
            ->when($selectedProfessionalId, fn ($query) => $query->where('professional_id', $selectedProfessionalId))
            ->where('status', 'pago')
            ->whereBetween('data_vencimento', [$chartStart, $now->copy()->endOfDay()])
            ->selectRaw('date(data_vencimento) as day, sum(valor_total_cents) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        $appointmentsChart = [];
        $revenueChart = [];
        foreach ($chartDays as $day) {
            $key = $day->toDateString();
            $appointmentsChart[] = (int) ($appointmentsSeries[$key] ?? 0);
            $revenueChart[] = (int) ($revenueSeries[$key] ?? 0);
        }

        $professionals = Professional::query()
            ->when($companyId, function ($query) use ($companyId) {
                $query->whereHas('user.companies', fn ($companyQuery) => $companyQuery->where('companies.id', $companyId));
            })
            ->orderBy('display_name')
            ->get();
        if ($selectedProfessionalId && ! $professionals->pluck('id')->contains($selectedProfessionalId)) {
            $selectedProfessionalId = null;
        }

        return view('dashboard', [
            'clinics' => $clinics,
            'units' => $units,
            'selectedClinicId' => $selectedClinicId,
            'selectedUnitId' => $selectedUnitId,
            'professionals' => $professionals,
            'selectedProfessionalId' => $selectedProfessionalId,
            'period' => $period,
            'periodLabels' => $periodLabels,
            'rangeLabel' => $rangeLabel,
            'appointmentsPeriodCount' => $appointmentsPeriodCount,
            'appointmentsPrevCount' => $appointmentsPrevCount,
            'confirmedCount' => $confirmedCount,
            'cancelledCount' => $cancelledCount,
            'patientsNewCount' => $patientsNewCount,
            'occupancyPercent' => $occupancyPercent,
            'occupiedHours' => $occupiedHours,
            'receitaCents' => $receitaCents,
            'receberAbertoCents' => $receberAbertoCents,
            'receberVencidas' => $receberVencidas,
            'pagarAbertasCents' => $pagarAbertasCents,
            'pagarVencidas' => $pagarVencidas,
            'statusToday' => $statusToday,
            'todayAppointments' => $todayAppointments,
            'waitingCount' => $waitingCount,
            'inProgressCount' => $inProgressCount,
            'weekDays' => $weekDays,
            'professionalStats' => $professionalStats,
            'appointmentsChart' => $appointmentsChart,
            'revenueChart' => $revenueChart,
        ]);
    }
}
