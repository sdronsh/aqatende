<?php

namespace App\Http\Controllers;

use App\Models\AccountPayable;
use App\Models\AccountReceivable;
use App\Models\Appointment;
use App\Models\CashFlowEntry;
use App\Models\Clinic;
use App\Models\Patient;
use App\Models\Professional;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\Unit;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ReportsController extends Controller
{
    public function index(Request $request): View
    {
        return view('sections.reports-index');
    }

    public function show(Request $request, string $report): View
    {
        $companyId = $request->session()->get('active_company_id');
        if (! $companyId) {
            abort(403);
        }

        $allowed = [
            'agenda',
            'atendimentos',
            'cancelamentos',
            'faltas',
            'receita',
            'contas_receber',
            'contas_pagar',
            'fluxo_caixa',
            'receita_profissional',
            'receita_servico',
            'pacientes_novos',
            'ocupacao_agenda',
            'atendimentos_profissional',
            'pacientes_lista',
            'pacientes_frequentes',
            'pacientes_sem_retorno',
            'taxa_cancelamento',
            'ticket_medio',
            'tempo_medio',
        ];
        if (! in_array($report, $allowed, true)) {
            abort(404);
        }

        $from = $this->parseDate($request->string('from')->toString()) ?? now()->startOfMonth();
        $to = $this->parseDate($request->string('to')->toString()) ?? now()->endOfDay();

        $clinicIds = Clinic::where('company_id', $companyId)->pluck('id');

        $filters = [
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'clinic_id' => $request->integer('clinic_id') ?: null,
            'unit_id' => $request->integer('unit_id') ?: null,
            'professional_id' => $request->integer('professional_id') ?: null,
            'service_id' => $request->integer('service_id') ?: null,
            'status' => $request->string('status')->toString(),
            'payment_method' => $request->string('payment_method')->toString(),
            'group_by' => $request->string('group_by', 'day')->toString(),
            'months' => $request->integer('months') ?: 6,
            'age_min' => $request->integer('age_min') ?: null,
            'age_max' => $request->integer('age_max') ?: null,
            'gender' => $request->string('gender')->toString(),
            'city' => $request->string('city')->toString(),
        ];

        if ($filters['clinic_id'] && ! $clinicIds->contains($filters['clinic_id'])) {
            $filters['clinic_id'] = null;
        }

        $units = Unit::whereIn('clinic_id', $clinicIds)->orderBy('name')->get();
        if ($filters['unit_id'] && ! $units->pluck('id')->contains($filters['unit_id'])) {
            $filters['unit_id'] = null;
        }

        $professionals = Professional::query()
            ->whereHas('user.companies', fn ($q) => $q->where('companies.id', $companyId))
            ->orderBy('display_name')
            ->get();
        if ($filters['professional_id'] && ! $professionals->pluck('id')->contains($filters['professional_id'])) {
            $filters['professional_id'] = null;
        }

        $services = Service::whereIn('clinic_id', $clinicIds)->orderBy('name')->get();
        if ($filters['service_id'] && ! $services->pluck('id')->contains($filters['service_id'])) {
            $filters['service_id'] = null;
        }

        $result = match ($report) {
            'agenda' => $this->reportAgenda($clinicIds, $filters),
            'atendimentos' => $this->reportAtendimentos($clinicIds, $filters),
            'cancelamentos' => $this->reportCancelamentos($clinicIds, $filters),
            'faltas' => $this->reportFaltas($clinicIds, $filters),
            'receita' => $this->reportReceita($clinicIds, $filters),
            'contas_receber' => $this->reportContasReceber($clinicIds, $filters),
            'contas_pagar' => $this->reportContasPagar($clinicIds, $filters),
            'fluxo_caixa' => $this->reportFluxoCaixa($clinicIds, $filters),
            'receita_profissional' => $this->reportReceitaPorProfissional($clinicIds, $filters),
            'receita_servico' => $this->reportReceitaPorServico($clinicIds, $filters),
            'pacientes_novos' => $this->reportPacientesNovos($companyId, $filters),
            'ocupacao_agenda' => $this->reportOcupacaoAgenda($companyId, $clinicIds, $filters),
            'atendimentos_profissional' => $this->reportAtendimentosPorProfissional($clinicIds, $filters),
            'pacientes_lista' => $this->reportPacientesLista($companyId, $filters),
            'pacientes_frequentes' => $this->reportPacientesFrequentes($clinicIds, $filters),
            'pacientes_sem_retorno' => $this->reportPacientesSemRetorno($clinicIds, $filters),
            'taxa_cancelamento' => $this->reportTaxaCancelamento($clinicIds, $filters),
            'ticket_medio' => $this->reportTicketMedio($clinicIds, $filters),
            'tempo_medio' => $this->reportTempoMedio($clinicIds, $filters),
            default => ['rows' => collect(), 'summary' => []],
        };

        return view('sections.reports-show', [
            'report' => $report,
            'filters' => $filters,
            'rows' => $result['rows'],
            'summary' => $result['summary'] ?? [],
            'clinics' => Clinic::whereIn('id', $clinicIds)->orderBy('name')->get(),
            'units' => $units,
            'professionals' => $professionals,
            'services' => $services,
        ]);
    }

    private function reportAgenda(Collection $clinicIds, array $filters): array
    {
        $query = Appointment::with(['patient', 'professional', 'service', 'clinic', 'unit'])
            ->whereBetween('scheduled_at', [$filters['from'], $filters['to']])
            ->whereIn('clinic_id', $clinicIds);

        $this->applyAppointmentFilters($query, $filters);

        return [
            'rows' => $query->orderBy('scheduled_at')->get(),
        ];
    }

    private function reportAtendimentos(Collection $clinicIds, array $filters): array
    {
        $query = Appointment::with(['patient', 'professional', 'service', 'clinic', 'unit'])
            ->whereBetween('scheduled_at', [$filters['from'], $filters['to']])
            ->whereIn('clinic_id', $clinicIds)
            ->whereIn('status', ['atendido', 'concluido']);

        $this->applyAppointmentFilters($query, $filters, ['status']);

        return [
            'rows' => $query->orderBy('scheduled_at')->get(),
        ];
    }

    private function reportCancelamentos(Collection $clinicIds, array $filters): array
    {
        $query = Appointment::with(['patient', 'professional', 'service', 'clinic', 'unit'])
            ->whereBetween('scheduled_at', [$filters['from'], $filters['to']])
            ->whereIn('clinic_id', $clinicIds)
            ->where('status', 'cancelado');

        $this->applyAppointmentFilters($query, $filters, ['status']);

        return [
            'rows' => $query->orderBy('scheduled_at')->get(),
        ];
    }

    private function reportFaltas(Collection $clinicIds, array $filters): array
    {
        $query = Appointment::with(['patient', 'professional', 'service', 'clinic', 'unit'])
            ->whereBetween('scheduled_at', [$filters['from'], $filters['to']])
            ->whereIn('clinic_id', $clinicIds)
            ->where('status', 'cancelado')
            ->where(function ($q) {
                $q->where('cancellation_reason', 'like', '%falta%')
                    ->orWhere('cancellation_reason', 'like', '%no show%')
                    ->orWhere('cancellation_reason', 'like', '%no-show%');
            });

        $this->applyAppointmentFilters($query, $filters, ['status']);

        return [
            'rows' => $query->orderBy('scheduled_at')->get(),
            'summary' => [
                'note' => 'Faltas baseadas no motivo de cancelamento.',
            ],
        ];
    }

    private function reportReceita(Collection $clinicIds, array $filters): array
    {
        $query = AccountReceivable::with(['patient', 'professional', 'appointment.service', 'clinic', 'unit'])
            ->whereIn('clinic_id', $clinicIds)
            ->where('status', 'pago')
            ->whereBetween('pago_em', [$filters['from'], $filters['to']]);

        if ($filters['professional_id']) {
            $query->where('professional_id', $filters['professional_id']);
        }
        if ($filters['unit_id']) {
            $query->where('unit_id', $filters['unit_id']);
        }
        if ($filters['payment_method']) {
            $query->where('forma_pagamento', $filters['payment_method']);
        }

        return [
            'rows' => $query->orderByDesc('pago_em')->get(),
            'summary' => [
                'total_cents' => (int) $query->sum('valor_total_cents'),
            ],
        ];
    }

    private function reportContasReceber(Collection $clinicIds, array $filters): array
    {
        $query = AccountReceivable::with(['patient', 'clinic', 'unit', 'professional'])
            ->whereIn('clinic_id', $clinicIds)
            ->whereBetween('data_vencimento', [$filters['from'], $filters['to']]);

        if ($filters['status']) {
            $query->where('status', $filters['status']);
        }
        if ($filters['payment_method']) {
            $query->where('forma_pagamento', $filters['payment_method']);
        }
        if ($filters['professional_id']) {
            $query->where('professional_id', $filters['professional_id']);
        }
        if ($filters['unit_id']) {
            $query->where('unit_id', $filters['unit_id']);
        }

        if (request()->boolean('overdue')) {
            $query->whereDate('data_vencimento', '<', now()->toDateString())
                ->where('status', '!=', 'pago');
        }

        return [
            'rows' => $query->orderBy('data_vencimento')->get(),
        ];
    }

    private function reportContasPagar(Collection $clinicIds, array $filters): array
    {
        $query = AccountPayable::with(['clinic', 'unit'])
            ->whereIn('clinic_id', $clinicIds)
            ->whereBetween('data_vencimento', [$filters['from'], $filters['to']]);

        if ($filters['status']) {
            $query->where('status', $filters['status']);
        }
        if ($filters['unit_id']) {
            $query->where('unit_id', $filters['unit_id']);
        }

        if (request()->boolean('overdue')) {
            $query->whereDate('data_vencimento', '<', now()->toDateString())
                ->where('status', '!=', 'pago');
        }

        return [
            'rows' => $query->orderBy('data_vencimento')->get(),
        ];
    }

    private function reportFluxoCaixa(Collection $clinicIds, array $filters): array
    {
        $group = in_array($filters['group_by'], ['day', 'week', 'month'], true) ? $filters['group_by'] : 'day';

        $query = CashFlowEntry::query()
            ->whereIn('clinic_id', $clinicIds)
            ->whereBetween('data_movimento', [$filters['from'], $filters['to']]);

        $rows = $query->get()->groupBy(function ($entry) use ($group) {
            $date = CarbonImmutable::parse($entry->data_movimento);
            return match ($group) {
                'week' => $date->startOfWeek()->toDateString(),
                'month' => $date->startOfMonth()->toDateString(),
                default => $date->toDateString(),
            };
        })->map(function ($items, $key) {
            $entrada = $items->where('tipo', 'entrada')->sum('valor_cents');
            $saida = $items->where('tipo', 'saida')->sum('valor_cents');
            return [
                'periodo' => $key,
                'entrada_cents' => (int) $entrada,
                'saida_cents' => (int) $saida,
                'saldo_cents' => (int) ($entrada - $saida),
            ];
        })->values();

        return [
            'rows' => $rows,
        ];
    }

    private function reportReceitaPorProfissional(Collection $clinicIds, array $filters): array
    {
        $query = AccountReceivable::query()
            ->whereIn('clinic_id', $clinicIds)
            ->where('status', 'pago')
            ->whereBetween('pago_em', [$filters['from'], $filters['to']]);

        $rows = $query->select('professional_id', DB::raw('COUNT(*) as total'), DB::raw('SUM(valor_total_cents) as total_cents'))
            ->groupBy('professional_id')
            ->orderByDesc('total_cents')
            ->get()
            ->map(function ($row) {
                $row->professional = Professional::find($row->professional_id);
                return $row;
            });

        return [
            'rows' => $rows,
        ];
    }

    private function reportReceitaPorServico(Collection $clinicIds, array $filters): array
    {
        $query = Appointment::query()
            ->whereIn('clinic_id', $clinicIds)
            ->whereIn('status', ['atendido', 'concluido'])
            ->whereBetween('scheduled_at', [$filters['from'], $filters['to']]);

        if ($filters['professional_id']) {
            $query->where('professional_id', $filters['professional_id']);
        }

        $rows = $query->select('service_id', DB::raw('COUNT(*) as total'), DB::raw('SUM(price_cents) as total_cents'))
            ->groupBy('service_id')
            ->orderByDesc('total_cents')
            ->get()
            ->map(function ($row) {
                $row->service = Service::find($row->service_id);
                return $row;
            });

        return [
            'rows' => $rows,
        ];
    }

    private function reportPacientesNovos(int $companyId, array $filters): array
    {
        $query = Patient::query()
            ->whereHas('companies', fn ($q) => $q->where('companies.id', $companyId))
            ->whereBetween('created_at', [$filters['from'], $filters['to']]);

        $rows = $query->get()->groupBy(function ($patient) {
            return $patient->created_at?->toDateString();
        })->map(function ($items, $date) {
            return [
                'data' => $date,
                'total' => $items->count(),
            ];
        })->values();

        return [
            'rows' => $rows,
        ];
    }

    private function reportOcupacaoAgenda(int $companyId, Collection $clinicIds, array $filters): array
    {
        $from = CarbonImmutable::parse($filters['from'])->startOfDay();
        $to = CarbonImmutable::parse($filters['to'])->endOfDay();

        $appointments = Appointment::query()
            ->whereIn('clinic_id', $clinicIds)
            ->whereBetween('scheduled_at', [$from, $to])
            ->whereNotIn('status', ['cancelado'])
            ->get()
            ->groupBy('professional_id');

        $unitIds = Unit::whereIn('clinic_id', $clinicIds)->pluck('id');
        $schedules = Schedule::query()
            ->whereIn('unit_id', $unitIds)
            ->where('is_active', true)
            ->get()
            ->groupBy('professional_id');

        $professionals = Professional::query()
            ->whereHas('user.companies', fn ($q) => $q->where('companies.id', $companyId))
            ->orderBy('display_name')
            ->get();

        $rows = $professionals->map(function ($professional) use ($from, $to, $appointments, $schedules) {
            $bookedMinutes = $appointments->get($professional->id, collect())
                ->sum(fn ($appointment) => (int) ($appointment->duration_minutes ?? 0));

            $availableMinutes = 0;
            foreach ($schedules->get($professional->id, collect()) as $schedule) {
                $minutes = $this->scheduleDurationMinutes($schedule->start_time, $schedule->end_time);
                $availableMinutes += $minutes * $this->weekdayCountBetween($from, $to, (int) $schedule->weekday);
            }

            $occupancy = $availableMinutes > 0 ? round(($bookedMinutes / $availableMinutes) * 100, 1) : 0;

            return [
                'professional' => $professional,
                'booked_minutes' => $bookedMinutes,
                'available_minutes' => $availableMinutes,
                'occupancy' => $occupancy,
            ];
        });

        return [
            'rows' => $rows,
            'summary' => [
                'note' => 'Ocupacao baseada em horarios cadastrados em Agenda.',
            ],
        ];
    }

    private function reportAtendimentosPorProfissional(Collection $clinicIds, array $filters): array
    {
        $query = Appointment::query()
            ->whereIn('clinic_id', $clinicIds)
            ->whereBetween('scheduled_at', [$filters['from'], $filters['to']]);

        $rows = $query->get()->groupBy('professional_id')->map(function ($items, $professionalId) {
            return [
                'professional' => Professional::find($professionalId),
                'total' => $items->count(),
                'confirmadas' => $items->whereIn('status', ['confirmado', 'atendido', 'concluido'])->count(),
                'canceladas' => $items->where('status', 'cancelado')->count(),
                'faltas' => $items->where('status', 'cancelado')
                    ->filter(fn ($a) => str_contains(strtolower((string) $a->cancellation_reason), 'falta'))
                    ->count(),
            ];
        })->values();

        return [
            'rows' => $rows,
        ];
    }

    private function reportPacientesLista(int $companyId, array $filters): array
    {
        $clinicIds = Clinic::where('company_id', $companyId)->pluck('id');

        $query = Patient::query()
            ->whereHas('companies', fn ($q) => $q->where('companies.id', $companyId))
            ->withMax([
                'appointments as last_appointment_at' => function ($appointmentQuery) use ($clinicIds) {
                    $appointmentQuery->whereIn('clinic_id', $clinicIds);
                },
            ], 'scheduled_at');

        if ($filters['gender']) {
            $query->where('gender', $filters['gender']);
        }
        if ($filters['city']) {
            $query->where('address_city', 'like', '%'.$filters['city'].'%');
        }
        if ($filters['from'] && $filters['to']) {
            $query->whereBetween('created_at', [$filters['from'], $filters['to']]);
        }
        if ($filters['age_min'] || $filters['age_max']) {
            $query->whereNotNull('birthdate');
            $query->where(function ($q) use ($filters) {
                if ($filters['age_min']) {
                    $q->whereDate('birthdate', '<=', now()->subYears($filters['age_min'])->toDateString());
                }
                if ($filters['age_max']) {
                    $q->whereDate('birthdate', '>=', now()->subYears($filters['age_max'])->toDateString());
                }
            });
        }

        return [
            'rows' => $query->orderBy('full_name')->get(),
        ];
    }

    private function reportPacientesFrequentes(Collection $clinicIds, array $filters): array
    {
        $rows = Appointment::query()
            ->whereIn('clinic_id', $clinicIds)
            ->whereBetween('scheduled_at', [$filters['from'], $filters['to']])
            ->select('patient_id', DB::raw('COUNT(*) as total'))
            ->groupBy('patient_id')
            ->orderByDesc('total')
            ->limit(50)
            ->get()
            ->map(function ($row) {
                $row->patient = Patient::find($row->patient_id);
                return $row;
            });

        return [
            'rows' => $rows,
        ];
    }

    private function reportPacientesSemRetorno(Collection $clinicIds, array $filters): array
    {
        $limitDate = now()->subMonths($filters['months'])->toDateString();

        $rows = Appointment::query()
            ->whereIn('clinic_id', $clinicIds)
            ->select('patient_id', DB::raw('MAX(scheduled_at) as last_visit'))
            ->groupBy('patient_id')
            ->havingRaw('MAX(scheduled_at) <= ?', [$limitDate])
            ->get()
            ->map(function ($row) {
                $row->patient = Patient::find($row->patient_id);
                return $row;
            });

        return [
            'rows' => $rows,
        ];
    }

    private function reportTaxaCancelamento(Collection $clinicIds, array $filters): array
    {
        $group = request()->string('group', 'profissional')->toString();
        if (! in_array($group, ['profissional', 'servico'], true)) {
            $group = 'profissional';
        }

        $query = Appointment::query()
            ->whereIn('clinic_id', $clinicIds)
            ->whereBetween('scheduled_at', [$filters['from'], $filters['to']]);

        $rows = $query->get()->groupBy($group === 'servico' ? 'service_id' : 'professional_id')
            ->map(function ($items, $key) use ($group) {
                $total = $items->count();
                $canceladas = $items->where('status', 'cancelado')->count();
                $taxa = $total > 0 ? round(($canceladas / $total) * 100, 1) : 0;

                return [
                    'label' => $group === 'servico'
                        ? (Service::find($key)?->name ?? 'Sem servico')
                        : (Professional::find($key)?->display_name ?? 'Sem profissional'),
                    'total' => $total,
                    'canceladas' => $canceladas,
                    'taxa' => $taxa,
                ];
            })->values();

        return [
            'rows' => $rows,
            'summary' => [
                'group' => $group,
            ],
        ];
    }

    private function reportTicketMedio(Collection $clinicIds, array $filters): array
    {
        $appointments = Appointment::query()
            ->whereIn('clinic_id', $clinicIds)
            ->whereBetween('scheduled_at', [$filters['from'], $filters['to']])
            ->whereIn('status', ['atendido', 'concluido'])
            ->get();

        $totalCents = (int) $appointments->sum('price_cents');
        $total = $appointments->count();
        $ticket = $total > 0 ? (int) round($totalCents / $total) : 0;

        return [
            'rows' => collect([[
                'total' => $total,
                'total_cents' => $totalCents,
                'ticket_cents' => $ticket,
            ]]),
            'summary' => [
                'total_cents' => $totalCents,
                'total' => $total,
                'ticket_cents' => $ticket,
            ],
        ];
    }

    private function reportTempoMedio(Collection $clinicIds, array $filters): array
    {
        $rows = Appointment::query()
            ->whereIn('clinic_id', $clinicIds)
            ->whereBetween('scheduled_at', [$filters['from'], $filters['to']])
            ->whereIn('status', ['atendido', 'concluido'])
            ->select('professional_id', DB::raw('AVG(duration_minutes) as avg_minutes'))
            ->groupBy('professional_id')
            ->orderByDesc('avg_minutes')
            ->get()
            ->map(function ($row) {
                $row->professional = Professional::find($row->professional_id);
                return $row;
            });

        return [
            'rows' => $rows,
        ];
    }

    private function applyAppointmentFilters($query, array $filters, array $ignore = []): void
    {
        if ($filters['clinic_id']) {
            $query->where('clinic_id', $filters['clinic_id']);
        }
        if ($filters['unit_id']) {
            $query->where('unit_id', $filters['unit_id']);
        }
        if ($filters['professional_id']) {
            $query->where('professional_id', $filters['professional_id']);
        }
        if ($filters['service_id']) {
            $query->where('service_id', $filters['service_id']);
        }
        if ($filters['status'] && ! in_array('status', $ignore, true)) {
            $query->where('status', $filters['status']);
        }
    }

    private function parseDate(string $value): ?CarbonImmutable
    {
        if (! $value) {
            return null;
        }

        try {
            return CarbonImmutable::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }

    private function scheduleDurationMinutes(?string $start, ?string $end): int
    {
        if (! $start || ! $end) {
            return 0;
        }

        [$sh, $sm] = array_map('intval', explode(':', $start));
        [$eh, $em] = array_map('intval', explode(':', $end));
        return max(0, (($eh * 60) + $em) - (($sh * 60) + $sm));
    }

    private function weekdayCountBetween(CarbonImmutable $from, CarbonImmutable $to, int $weekday): int
    {
        $count = 0;
        $cursor = $from->startOfDay();
        $end = $to->startOfDay();
        while ($cursor <= $end) {
            if ((int) $cursor->dayOfWeekIso === $weekday) {
                $count++;
            }
            $cursor = $cursor->addDay();
        }
        return $count;
    }
}
