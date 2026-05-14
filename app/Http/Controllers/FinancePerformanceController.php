<?php

namespace App\Http\Controllers;

use App\Models\AccountPayable;
use App\Models\AccountReceivable;
use App\Models\Appointment;
use App\Models\Clinic;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class FinancePerformanceController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:financeiro.relatorios.view')->only(['index']);
    }

    public function index(Request $request): View
    {
        $companyId = $request->session()->get('active_company_id');
        if (! $companyId) {
            abort(403);
        }

        $clinicIds = Clinic::where('company_id', $companyId)->pluck('id');
        $month = $this->selectedMonth($request);
        $includeFuture = $request->boolean('include_future');
        [$from, $to] = $this->periodForMonth($month, $includeFuture);
        [$previousFrom, $previousTo] = $this->previousPeriod($from, $to);

        $revenueCents = $this->revenueCents($clinicIds, $from, $to);
        $expenseCents = $this->expenseCents($clinicIds, $from, $to);
        $profitCents = $revenueCents - $expenseCents;

        $previousRevenueCents = $this->revenueCents($clinicIds, $previousFrom, $previousTo);
        $previousExpenseCents = $this->expenseCents($clinicIds, $previousFrom, $previousTo);
        $previousProfitCents = $previousRevenueCents - $previousExpenseCents;

        $paymentMethods = $this->paymentMethods($clinicIds, $from, $to, $revenueCents);
        $effort = $this->effort($clinicIds, $from, $to);
        $topServices = $this->topServices($clinicIds, $from, $to);

        return view('finance.performance.index', [
            'month' => $month,
            'includeFuture' => $includeFuture,
            'from' => $from,
            'to' => $to,
            'revenueCents' => $revenueCents,
            'expenseCents' => $expenseCents,
            'profitCents' => $profitCents,
            'revenueChange' => $this->percentageChange($revenueCents, $previousRevenueCents),
            'expenseChange' => $this->percentageChange($expenseCents, $previousExpenseCents),
            'profitChange' => $this->percentageChange($profitCents, $previousProfitCents),
            'paymentMethods' => $paymentMethods,
            'effort' => $effort,
            'topServices' => $topServices,
        ]);
    }

    private function selectedMonth(Request $request): Carbon
    {
        $month = $request->string('month')->toString();

        if (preg_match('/^\d{4}-\d{2}$/', $month)) {
            return Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        }

        return now()->startOfMonth();
    }

    private function periodForMonth(Carbon $month, bool $includeFuture): array
    {
        $from = $month->copy()->startOfMonth()->startOfDay();
        $to = $month->copy()->endOfMonth()->endOfDay();

        if (! $includeFuture && $month->isSameMonth(now())) {
            $to = now()->endOfDay();
        }

        return [$from, $to];
    }

    private function previousPeriod(Carbon $from, Carbon $to): array
    {
        $previousFrom = $from->copy()->subMonthNoOverflow()->startOfDay();
        $previousTo = $previousFrom->copy()->addDays($from->diffInDays($to))->endOfDay();

        return [$previousFrom, $previousTo];
    }

    private function revenueCents(Collection $clinicIds, Carbon $from, Carbon $to): int
    {
        return (int) AccountReceivable::query()
            ->whereIn('clinic_id', $clinicIds)
            ->where('status', '!=', 'cancelado')
            ->whereBetween('data_vencimento', [$from->toDateString(), $to->toDateString()])
            ->sum('valor_total_cents');
    }

    private function expenseCents(Collection $clinicIds, Carbon $from, Carbon $to): int
    {
        return (int) AccountPayable::query()
            ->whereIn('clinic_id', $clinicIds)
            ->whereBetween('data_vencimento', [$from->toDateString(), $to->toDateString()])
            ->sum('valor_cents');
    }

    private function paymentMethods(Collection $clinicIds, Carbon $from, Carbon $to, int $revenueCents): array
    {
        $totals = AccountReceivable::query()
            ->select('forma_pagamento', DB::raw('SUM(valor_total_cents) as total_cents'))
            ->whereIn('clinic_id', $clinicIds)
            ->where('status', 'pago')
            ->whereBetween('data_vencimento', [$from->toDateString(), $to->toDateString()])
            ->groupBy('forma_pagamento')
            ->pluck('total_cents', 'forma_pagamento');

        $methods = [
            'dinheiro' => ['label' => 'Dinheiro', 'icon' => '$'],
            'pix' => ['label' => 'PIX / Transf.', 'icon' => 'PIX'],
            'cartao' => ['label' => 'Credito', 'icon' => 'CC'],
            'debito' => ['label' => 'Debito', 'icon' => 'DB'],
            'convenio' => ['label' => 'Cortesia', 'icon' => 'CT'],
        ];

        return collect($methods)->map(function (array $method, string $key) use ($totals, $revenueCents) {
            $total = (int) ($totals[$key] ?? 0);

            return [
                'key' => $key,
                'label' => $method['label'],
                'icon' => $method['icon'],
                'total_cents' => $total,
                'percent' => $revenueCents > 0 ? round(($total / $revenueCents) * 100, 1) : 0,
            ];
        })->values()->all();
    }

    private function effort(Collection $clinicIds, Carbon $from, Carbon $to): array
    {
        $appointments = Appointment::query()
            ->whereIn('clinic_id', $clinicIds)
            ->whereBetween('scheduled_at', [$from, $to])
            ->whereIn('status', ['atendido', 'concluido', 'attended', 'done'])
            ->get(['id', 'patient_id', 'duration_minutes', 'scheduled_at', 'ends_at']);

        $minutes = $appointments->sum(function (Appointment $appointment) {
            if ($appointment->duration_minutes) {
                return (int) $appointment->duration_minutes;
            }

            if ($appointment->scheduled_at && $appointment->ends_at) {
                return max(0, $appointment->scheduled_at->diffInMinutes($appointment->ends_at));
            }

            return 0;
        });

        return [
            'days' => $from->copy()->startOfDay()->diffInDays($to->copy()->startOfDay()) + 1,
            'appointments' => $appointments->count(),
            'patients' => $appointments->pluck('patient_id')->filter()->unique()->count(),
            'hours' => $minutes > 0 ? round($minutes / 60, 1) : null,
        ];
    }

    private function topServices(Collection $clinicIds, Carbon $from, Carbon $to): Collection
    {
        $rows = DB::table('appointment_service')
            ->join('appointments', 'appointments.id', '=', 'appointment_service.appointment_id')
            ->join('services', 'services.id', '=', 'appointment_service.service_id')
            ->whereIn('appointments.clinic_id', $clinicIds)
            ->whereBetween('appointments.scheduled_at', [$from, $to])
            ->whereNotIn('appointments.status', ['cancelado', 'cancelled'])
            ->groupBy('services.id', 'services.name')
            ->orderByDesc(DB::raw('SUM(COALESCE(appointment_service.price_cents, services.price_cents, 0))'))
            ->limit(5)
            ->get([
                'services.name',
                DB::raw('COUNT(*) as quantity'),
                DB::raw('SUM(COALESCE(appointment_service.price_cents, services.price_cents, 0)) as total_cents'),
            ]);

        $total = max(1, (int) $rows->sum('total_cents'));

        return $rows->map(function ($row) use ($total) {
            return [
                'name' => $row->name,
                'quantity' => (int) $row->quantity,
                'total_cents' => (int) $row->total_cents,
                'percent' => round(((int) $row->total_cents / $total) * 100),
            ];
        });
    }

    private function percentageChange(int $current, int $previous): ?float
    {
        if ($previous === 0) {
            return $current === 0 ? 0.0 : null;
        }

        return round((($current - $previous) / abs($previous)) * 100, 1);
    }
}
