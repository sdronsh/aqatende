@php
    $money = static fn (?int $cents) => 'R$ ' . number_format(((int) $cents) / 100, 2, ',', '.');
    $sumColumn = static fn ($rows, string $column) => $rows->sum(fn ($row) => (int) ($row->{$column} ?? 0));
    $sumArrayColumn = static fn ($rows, string $column) => $rows->sum(fn ($row) => (int) ($row[$column] ?? 0));
    $reportTitles = [
        'agenda' => 'Agenda',
        'atendimentos' => 'Atendimentos realizados',
        'cancelamentos' => 'Cancelamentos',
        'faltas' => 'Faltas',
        'receita' => 'Receita',
        'contas_receber' => 'Contas a receber',
        'contas_pagar' => 'Contas a pagar',
        'fluxo_caixa' => 'Fluxo de caixa',
        'receita_profissional' => 'Receita por profissional',
        'receita_servico' => 'Receita por servico',
        'pacientes_novos' => 'Clientes novos',
        'ocupacao_agenda' => 'Ocupacao da agenda',
        'atendimentos_profissional' => 'Atendimentos por profissional',
        'pacientes_lista' => 'Lista de clientes',
        'pacientes_frequentes' => 'Clientes frequentes',
        'pacientes_sem_retorno' => 'Clientes sem retorno',
        'taxa_cancelamento' => 'Taxa de cancelamento',
        'ticket_medio' => 'Ticket medio',
        'tempo_medio' => 'Tempo medio de consulta',
    ];
    $reportTitle = $reportTitles[$report] ?? 'Relatorio';
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-lg font-semibold text-gray-800">{{ $reportTitle }}</h2>
                <p class="text-sm text-gray-600">Filtros e resultados.</p>
            </div>
            <div class="no-print flex flex-wrap items-center gap-2">
                <button type="button" class="inline-flex items-center justify-center rounded-lg bg-brand-500 px-4 py-2 text-sm font-semibold text-white shadow-theme-xs transition hover:bg-brand-600" data-print-report>
                    Gerar PDF
                </button>
                <a class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-theme-xs transition hover:bg-gray-50" href="{{ route('finance.reports') }}">Voltar</a>
            </div>
        </div>
    </x-slot>

    <style>
        .print-only {
            display: none;
        }

        @media print {
            @page {
                margin: 12mm;
            }

            body {
                background: #fff !important;
            }

            aside,
            header,
            .no-print,
            .report-filter-card {
                display: none !important;
            }

            #main-content {
                margin: 0 !important;
                width: 100% !important;
            }

            #main-content > div {
                max-width: none !important;
                padding: 0 !important;
            }

            .print-only {
                display: block !important;
            }

            .report-print-card {
                border: 0 !important;
                box-shadow: none !important;
            }

            .report-print-card .overflow-x-auto {
                overflow: visible !important;
            }

            .report-print-card table {
                width: 100% !important;
                border-collapse: collapse !important;
                border-spacing: 0 !important;
                font-size: 11px !important;
            }

            .report-print-card th,
            .report-print-card td {
                border: 1px solid #d0d5dd !important;
                padding: 4px 6px !important;
            }
        }
    </style>

    <div class="space-y-4">
        <div class="print-only">
            <h1 class="text-xl font-semibold text-gray-900">{{ $reportTitle }}</h1>
            <p class="mt-1 text-sm text-gray-600">
                Periodo: {{ \Carbon\Carbon::parse($filters['from'])->format('d/m/Y') }} a {{ \Carbon\Carbon::parse($filters['to'])->format('d/m/Y') }}
            </p>
            <p class="mt-1 text-xs text-gray-500">Gerado em {{ now()->format('d/m/Y H:i') }}</p>
        </div>

        <div class="report-filter-card rounded-xl border border-gray-200 bg-white p-4 shadow-theme-sm md:p-6">
            <form class="grid gap-3 md:grid-cols-12" method="GET">
                <div class="md:col-span-3">
                    <label class="mb-1 block text-xs font-semibold text-gray-600">De</label>
                    <input type="date" name="from" value="{{ $filters['from'] }}" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm text-gray-700" />
                </div>
                <div class="md:col-span-3">
                    <label class="mb-1 block text-xs font-semibold text-gray-600">Ate</label>
                    <input type="date" name="to" value="{{ $filters['to'] }}" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm text-gray-700" />
                </div>

                @if (in_array($report, ['agenda', 'atendimentos', 'cancelamentos', 'faltas', 'contas_receber', 'contas_pagar'], true))
                    <div class="md:col-span-3">
                        <label class="mb-1 block text-xs font-semibold text-gray-600">Clinica</label>
                        <select name="clinic_id" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm text-gray-700">
                            <option value="">Todas</option>
                            @foreach ($clinics as $clinic)
                                <option value="{{ $clinic->id }}" @selected((string) $filters['clinic_id'] === (string) $clinic->id)>{{ $clinic->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="md:col-span-3">
                        <label class="mb-1 block text-xs font-semibold text-gray-600">Unidade</label>
                        <select name="unit_id" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm text-gray-700">
                            <option value="">Todas</option>
                            @foreach ($units as $unit)
                                <option value="{{ $unit->id }}" @selected((string) $filters['unit_id'] === (string) $unit->id)>{{ $unit->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                @if (in_array($report, ['agenda', 'atendimentos', 'cancelamentos', 'faltas', 'receita', 'contas_receber', 'receita_profissional', 'receita_servico', 'atendimentos_profissional', 'taxa_cancelamento', 'tempo_medio'], true))
                    <div class="md:col-span-3">
                        <label class="mb-1 block text-xs font-semibold text-gray-600">Profissional</label>
                        <select name="professional_id" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm text-gray-700">
                            <option value="">Todos</option>
                            @foreach ($professionals as $professional)
                                <option value="{{ $professional->id }}" @selected((string) $filters['professional_id'] === (string) $professional->id)>{{ $professional->display_name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                @if (in_array($report, ['agenda', 'atendimentos', 'cancelamentos', 'faltas', 'receita_servico', 'atendimentos_profissional'], true))
                    <div class="md:col-span-3">
                        <label class="mb-1 block text-xs font-semibold text-gray-600">Servico</label>
                        <select name="service_id" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm text-gray-700">
                            <option value="">Todos</option>
                            @foreach ($services as $service)
                                <option value="{{ $service->id }}" @selected((string) $filters['service_id'] === (string) $service->id)>{{ $service->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                @if (in_array($report, ['agenda', 'contas_receber', 'contas_pagar'], true))
                    <div class="md:col-span-3">
                        <label class="mb-1 block text-xs font-semibold text-gray-600">Status</label>
                        <input type="text" name="status" value="{{ $filters['status'] }}" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm text-gray-700" placeholder="agendado, pago..." />
                    </div>
                @endif

                @if (in_array($report, ['receita', 'contas_receber'], true))
                    <div class="md:col-span-3">
                        <label class="mb-1 block text-xs font-semibold text-gray-600">Forma pagamento</label>
                        <input type="text" name="payment_method" value="{{ $filters['payment_method'] }}" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm text-gray-700" placeholder="pix, cartao..." />
                    </div>
                @endif

                @if ($report === 'fluxo_caixa')
                    <div class="md:col-span-3">
                        <label class="mb-1 block text-xs font-semibold text-gray-600">Agrupar por</label>
                        <select name="group_by" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm text-gray-700">
                            <option value="day" @selected($filters['group_by'] === 'day')>Diario</option>
                            <option value="week" @selected($filters['group_by'] === 'week')>Semanal</option>
                            <option value="month" @selected($filters['group_by'] === 'month')>Mensal</option>
                        </select>
                    </div>
                @endif

                @if ($report === 'pacientes_lista')
                    <div class="md:col-span-3">
                        <label class="mb-1 block text-xs font-semibold text-gray-600">Idade minima</label>
                        <input type="number" name="age_min" value="{{ $filters['age_min'] }}" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm text-gray-700" />
                    </div>
                    <div class="md:col-span-3">
                        <label class="mb-1 block text-xs font-semibold text-gray-600">Idade maxima</label>
                        <input type="number" name="age_max" value="{{ $filters['age_max'] }}" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm text-gray-700" />
                    </div>
                    <div class="md:col-span-3">
                        <label class="mb-1 block text-xs font-semibold text-gray-600">Sexo</label>
                        <input type="text" name="gender" value="{{ $filters['gender'] }}" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm text-gray-700" />
                    </div>
                    <div class="md:col-span-3">
                        <label class="mb-1 block text-xs font-semibold text-gray-600">Cidade</label>
                        <input type="text" name="city" value="{{ $filters['city'] }}" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm text-gray-700" />
                    </div>
                @endif

                @if ($report === 'pacientes_sem_retorno')
                    <div class="md:col-span-3">
                        <label class="mb-1 block text-xs font-semibold text-gray-600">Meses sem retorno</label>
                        <input type="number" name="months" value="{{ $filters['months'] }}" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm text-gray-700" />
                    </div>
                @endif

                @if ($report === 'taxa_cancelamento')
                    <div class="md:col-span-3">
                        <label class="mb-1 block text-xs font-semibold text-gray-600">Agrupar por</label>
                        <select name="group" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm text-gray-700">
                            <option value="profissional" @selected(($summary['group'] ?? '') === 'profissional')>Profissional</option>
                            <option value="servico" @selected(($summary['group'] ?? '') === 'servico')>Servico</option>
                        </select>
                    </div>
                @endif

                <div class="md:col-span-12">
                    <button class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-semibold text-white">Gerar relatorio</button>
                </div>
            </form>
        </div>

        <div class="report-print-card rounded-xl border border-gray-200 bg-white shadow-theme-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full border-separate border border-gray-200 [border-spacing:0] text-sm">
                    <thead class="bg-gray-50">
                        @switch($report)
                            @case('agenda')
                                <tr class="text-left text-xs font-semibold uppercase text-gray-500">
                                    <th class="border border-gray-200 px-3 py-2">Data</th>
                                    <th class="border border-gray-200 px-3 py-2">Hora</th>
                                    <th class="border border-gray-200 px-3 py-2">Cliente</th>
                                    <th class="border border-gray-200 px-3 py-2">Profissional</th>
                                    <th class="border border-gray-200 px-3 py-2">Servico</th>
                                    <th class="border border-gray-200 px-3 py-2">Status</th>
                                    <th class="border border-gray-200 px-3 py-2">Canal</th>
                                </tr>
                                @break
                            @case('atendimentos')
                                <tr class="text-left text-xs font-semibold uppercase text-gray-500">
                                    <th class="border border-gray-200 px-3 py-2">Data</th>
                                    <th class="border border-gray-200 px-3 py-2">Cliente</th>
                                    <th class="border border-gray-200 px-3 py-2">Profissional</th>
                                    <th class="border border-gray-200 px-3 py-2">Servico</th>
                                    <th class="border border-gray-200 px-3 py-2">Duracao</th>
                                    <th class="border border-gray-200 px-3 py-2">Valor</th>
                                </tr>
                                @break
                            @case('cancelamentos')
                                <tr class="text-left text-xs font-semibold uppercase text-gray-500">
                                    <th class="border border-gray-200 px-3 py-2">Data</th>
                                    <th class="border border-gray-200 px-3 py-2">Cliente</th>
                                    <th class="border border-gray-200 px-3 py-2">Profissional</th>
                                    <th class="border border-gray-200 px-3 py-2">Motivo</th>
                                    <th class="border border-gray-200 px-3 py-2">Antecedencia (h)</th>
                                </tr>
                                @break
                            @case('faltas')
                                <tr class="text-left text-xs font-semibold uppercase text-gray-500">
                                    <th class="border border-gray-200 px-3 py-2">Cliente</th>
                                    <th class="border border-gray-200 px-3 py-2">Profissional</th>
                                    <th class="border border-gray-200 px-3 py-2">Data</th>
                                    <th class="border border-gray-200 px-3 py-2">Servico</th>
                                </tr>
                                @break
                            @case('receita')
                                <tr class="text-left text-xs font-semibold uppercase text-gray-500">
                                    <th class="border border-gray-200 px-3 py-2">Data</th>
                                    <th class="border border-gray-200 px-3 py-2">Cliente</th>
                                    <th class="border border-gray-200 px-3 py-2">Profissional</th>
                                    <th class="border border-gray-200 px-3 py-2">Servico</th>
                                    <th class="border border-gray-200 px-3 py-2">Forma</th>
                                    <th class="border border-gray-200 px-3 py-2">Valor</th>
                                </tr>
                                @break
                            @case('contas_receber')
                                <tr class="text-left text-xs font-semibold uppercase text-gray-500">
                                    <th class="border border-gray-200 px-3 py-2">Cliente</th>
                                    <th class="border border-gray-200 px-3 py-2">Descricao</th>
                                    <th class="border border-gray-200 px-3 py-2">Valor</th>
                                    <th class="border border-gray-200 px-3 py-2">Vencimento</th>
                                    <th class="border border-gray-200 px-3 py-2">Status</th>
                                    <th class="border border-gray-200 px-3 py-2">Forma</th>
                                </tr>
                                @break
                            @case('contas_pagar')
                                <tr class="text-left text-xs font-semibold uppercase text-gray-500">
                                    <th class="border border-gray-200 px-3 py-2">Fornecedor</th>
                                    <th class="border border-gray-200 px-3 py-2">Descricao</th>
                                    <th class="border border-gray-200 px-3 py-2">Valor</th>
                                    <th class="border border-gray-200 px-3 py-2">Vencimento</th>
                                    <th class="border border-gray-200 px-3 py-2">Status</th>
                                </tr>
                                @break
                            @case('fluxo_caixa')
                                <tr class="text-left text-xs font-semibold uppercase text-gray-500">
                                    <th class="border border-gray-200 px-3 py-2">Periodo</th>
                                    <th class="border border-gray-200 px-3 py-2">Entradas</th>
                                    <th class="border border-gray-200 px-3 py-2">Saidas</th>
                                    <th class="border border-gray-200 px-3 py-2">Saldo</th>
                                </tr>
                                @break
                            @case('receita_profissional')
                                <tr class="text-left text-xs font-semibold uppercase text-gray-500">
                                    <th class="border border-gray-200 px-3 py-2">Profissional</th>
                                    <th class="border border-gray-200 px-3 py-2">Atendimentos</th>
                                    <th class="border border-gray-200 px-3 py-2">Faturamento</th>
                                </tr>
                                @break
                            @case('receita_servico')
                                <tr class="text-left text-xs font-semibold uppercase text-gray-500">
                                    <th class="border border-gray-200 px-3 py-2">Servico</th>
                                    <th class="border border-gray-200 px-3 py-2">Quantidade</th>
                                    <th class="border border-gray-200 px-3 py-2">Faturamento</th>
                                </tr>
                                @break
                            @case('pacientes_novos')
                                <tr class="text-left text-xs font-semibold uppercase text-gray-500">
                                    <th class="border border-gray-200 px-3 py-2">Data</th>
                                    <th class="border border-gray-200 px-3 py-2">Quantidade</th>
                                </tr>
                                @break
                            @case('ocupacao_agenda')
                                <tr class="text-left text-xs font-semibold uppercase text-gray-500">
                                    <th class="border border-gray-200 px-3 py-2">Profissional</th>
                                    <th class="border border-gray-200 px-3 py-2">Minutos ocupados</th>
                                    <th class="border border-gray-200 px-3 py-2">Minutos disponiveis</th>
                                    <th class="border border-gray-200 px-3 py-2">Ocupacao</th>
                                </tr>
                                @break
                            @case('atendimentos_profissional')
                                <tr class="text-left text-xs font-semibold uppercase text-gray-500">
                                    <th class="border border-gray-200 px-3 py-2">Profissional</th>
                                    <th class="border border-gray-200 px-3 py-2">Total</th>
                                    <th class="border border-gray-200 px-3 py-2">Confirmadas</th>
                                    <th class="border border-gray-200 px-3 py-2">Canceladas</th>
                                    <th class="border border-gray-200 px-3 py-2">Faltas</th>
                                </tr>
                                @break
                            @case('pacientes_lista')
                                <tr class="text-left text-xs font-semibold uppercase text-gray-500">
                                    <th class="border border-gray-200 px-3 py-2">Nome</th>
                                    <th class="border border-gray-200 px-3 py-2">Telefone</th>
                                    <th class="border border-gray-200 px-3 py-2">Cidade</th>
                                    <th class="border border-gray-200 px-3 py-2">Ultimo atendimento</th>
                                </tr>
                                @break
                            @case('pacientes_frequentes')
                                <tr class="text-left text-xs font-semibold uppercase text-gray-500">
                                    <th class="border border-gray-200 px-3 py-2">Cliente</th>
                                    <th class="border border-gray-200 px-3 py-2">Atendimentos</th>
                                </tr>
                                @break
                            @case('pacientes_sem_retorno')
                                <tr class="text-left text-xs font-semibold uppercase text-gray-500">
                                    <th class="border border-gray-200 px-3 py-2">Cliente</th>
                                    <th class="border border-gray-200 px-3 py-2">Ultimo atendimento</th>
                                </tr>
                                @break
                            @case('taxa_cancelamento')
                                <tr class="text-left text-xs font-semibold uppercase text-gray-500">
                                    <th class="border border-gray-200 px-3 py-2">Grupo</th>
                                    <th class="border border-gray-200 px-3 py-2">Total</th>
                                    <th class="border border-gray-200 px-3 py-2">Canceladas</th>
                                    <th class="border border-gray-200 px-3 py-2">Taxa</th>
                                </tr>
                                @break
                            @case('ticket_medio')
                                <tr class="text-left text-xs font-semibold uppercase text-gray-500">
                                    <th class="border border-gray-200 px-3 py-2">Total consultas</th>
                                    <th class="border border-gray-200 px-3 py-2">Receita total</th>
                                    <th class="border border-gray-200 px-3 py-2">Ticket medio</th>
                                </tr>
                                @break
                            @case('tempo_medio')
                                <tr class="text-left text-xs font-semibold uppercase text-gray-500">
                                    <th class="border border-gray-200 px-3 py-2">Profissional</th>
                                    <th class="border border-gray-200 px-3 py-2">Tempo medio (min)</th>
                                </tr>
                                @break
                        @endswitch
                    </thead>
                    <tbody>
                        @forelse ($rows as $row)
                            @switch($report)
                                @case('agenda')
                                    <tr class="odd:bg-white even:bg-gray-50">
                                        <td class="border border-gray-200 px-3 py-2">{{ $row->scheduled_at?->format('d/m/Y') }}</td>
                                        <td class="border border-gray-200 px-3 py-2">{{ $row->scheduled_at?->format('H:i') }}</td>
                                        <td class="border border-gray-200 px-3 py-2">{{ $row->patient?->full_name }}</td>
                                        <td class="border border-gray-200 px-3 py-2">{{ $row->professional?->display_name }}</td>
                                        <td class="border border-gray-200 px-3 py-2">{{ $row->service?->name }}</td>
                                        <td class="border border-gray-200 px-3 py-2">{{ $row->status }}</td>
                                        <td class="border border-gray-200 px-3 py-2">{{ ['presencial' => 'Presencial', 'home_care' => 'Home Care', 'whatsapp' => 'Home Care', 'teleconsulta' => 'Home Care', 'walk_in' => 'Fila'][$row->channel ?? 'presencial'] ?? $row->channel }}</td>
                                    </tr>
                                    @break
                                @case('atendimentos')
                                    <tr class="odd:bg-white even:bg-gray-50">
                                        <td class="border border-gray-200 px-3 py-2">{{ $row->scheduled_at?->format('d/m/Y') }}</td>
                                        <td class="border border-gray-200 px-3 py-2">{{ $row->patient?->full_name }}</td>
                                        <td class="border border-gray-200 px-3 py-2">{{ $row->professional?->display_name }}</td>
                                        <td class="border border-gray-200 px-3 py-2">{{ $row->service?->name }}</td>
                                        <td class="border border-gray-200 px-3 py-2">{{ $row->duration_minutes ?? '-' }}</td>
                                        <td class="border border-gray-200 px-3 py-2">{{ $money($row->price_cents) }}</td>
                                    </tr>
                                    @break
                                @case('cancelamentos')
                                    @php
                                        $hours = $row->cancelled_at ? round($row->scheduled_at?->diffInMinutes($row->cancelled_at, false) / 60, 1) : null;
                                    @endphp
                                    <tr class="odd:bg-white even:bg-gray-50">
                                        <td class="border border-gray-200 px-3 py-2">{{ $row->scheduled_at?->format('d/m/Y') }}</td>
                                        <td class="border border-gray-200 px-3 py-2">{{ $row->patient?->full_name }}</td>
                                        <td class="border border-gray-200 px-3 py-2">{{ $row->professional?->display_name }}</td>
                                        <td class="border border-gray-200 px-3 py-2">{{ $row->cancellation_reason ?? '-' }}</td>
                                        <td class="border border-gray-200 px-3 py-2">{{ $hours !== null ? $hours : '-' }}</td>
                                    </tr>
                                    @break
                                @case('faltas')
                                    <tr class="odd:bg-white even:bg-gray-50">
                                        <td class="border border-gray-200 px-3 py-2">{{ $row->patient?->full_name }}</td>
                                        <td class="border border-gray-200 px-3 py-2">{{ $row->professional?->display_name }}</td>
                                        <td class="border border-gray-200 px-3 py-2">{{ $row->scheduled_at?->format('d/m/Y') }}</td>
                                        <td class="border border-gray-200 px-3 py-2">{{ $row->service?->name }}</td>
                                    </tr>
                                    @break
                                @case('receita')
                                    <tr class="odd:bg-white even:bg-gray-50">
                                        <td class="border border-gray-200 px-3 py-2">{{ $row->pago_em?->format('d/m/Y') }}</td>
                                        <td class="border border-gray-200 px-3 py-2">{{ $row->patient?->full_name }}</td>
                                        <td class="border border-gray-200 px-3 py-2">{{ $row->professional?->display_name }}</td>
                                        <td class="border border-gray-200 px-3 py-2">{{ $row->appointment?->service?->name }}</td>
                                        <td class="border border-gray-200 px-3 py-2">{{ $row->forma_pagamento }}</td>
                                        <td class="border border-gray-200 px-3 py-2">{{ $money($row->valor_total_cents) }}</td>
                                    </tr>
                                    @break
                                @case('contas_receber')
                                    <tr class="odd:bg-white even:bg-gray-50">
                                        <td class="border border-gray-200 px-3 py-2">{{ $row->patient?->full_name }}</td>
                                        <td class="border border-gray-200 px-3 py-2">{{ $row->descricao }}</td>
                                        <td class="border border-gray-200 px-3 py-2">{{ $money($row->valor_total_cents) }}</td>
                                        <td class="border border-gray-200 px-3 py-2">{{ $row->data_vencimento?->format('d/m/Y') }}</td>
                                        <td class="border border-gray-200 px-3 py-2">{{ $row->status }}</td>
                                        <td class="border border-gray-200 px-3 py-2">{{ $row->forma_pagamento }}</td>
                                    </tr>
                                    @break
                                @case('contas_pagar')
                                    <tr class="odd:bg-white even:bg-gray-50">
                                        <td class="border border-gray-200 px-3 py-2">{{ $row->fornecedor }}</td>
                                        <td class="border border-gray-200 px-3 py-2">{{ $row->descricao }}</td>
                                        <td class="border border-gray-200 px-3 py-2">{{ $money($row->valor_cents) }}</td>
                                        <td class="border border-gray-200 px-3 py-2">{{ $row->data_vencimento?->format('d/m/Y') }}</td>
                                        <td class="border border-gray-200 px-3 py-2">{{ $row->status }}</td>
                                    </tr>
                                    @break
                                @case('fluxo_caixa')
                                    <tr class="odd:bg-white even:bg-gray-50">
                                        <td class="border border-gray-200 px-3 py-2">{{ $row['periodo'] }}</td>
                                        <td class="border border-gray-200 px-3 py-2">{{ $money($row['entrada_cents']) }}</td>
                                        <td class="border border-gray-200 px-3 py-2">{{ $money($row['saida_cents']) }}</td>
                                        <td class="border border-gray-200 px-3 py-2">{{ $money($row['saldo_cents']) }}</td>
                                    </tr>
                                    @break
                                @case('receita_profissional')
                                    <tr class="odd:bg-white even:bg-gray-50">
                                        <td class="border border-gray-200 px-3 py-2">{{ $row->professional?->display_name }}</td>
                                        <td class="border border-gray-200 px-3 py-2">{{ $row->total }}</td>
                                        <td class="border border-gray-200 px-3 py-2">{{ $money($row->total_cents) }}</td>
                                    </tr>
                                    @break
                                @case('receita_servico')
                                    <tr class="odd:bg-white even:bg-gray-50">
                                        <td class="border border-gray-200 px-3 py-2">{{ $row->service?->name }}</td>
                                        <td class="border border-gray-200 px-3 py-2">{{ $row->total }}</td>
                                        <td class="border border-gray-200 px-3 py-2">{{ $money($row->total_cents) }}</td>
                                    </tr>
                                    @break
                                @case('pacientes_novos')
                                    <tr class="odd:bg-white even:bg-gray-50">
                                        <td class="border border-gray-200 px-3 py-2">{{ $row['data'] }}</td>
                                        <td class="border border-gray-200 px-3 py-2">{{ $row['total'] }}</td>
                                    </tr>
                                    @break
                                @case('ocupacao_agenda')
                                    <tr class="odd:bg-white even:bg-gray-50">
                                        <td class="border border-gray-200 px-3 py-2">{{ $row['professional']?->display_name }}</td>
                                        <td class="border border-gray-200 px-3 py-2">{{ $row['booked_minutes'] }}</td>
                                        <td class="border border-gray-200 px-3 py-2">{{ $row['available_minutes'] }}</td>
                                        <td class="border border-gray-200 px-3 py-2">{{ $row['occupancy'] }}%</td>
                                    </tr>
                                    @break
                                @case('atendimentos_profissional')
                                    <tr class="odd:bg-white even:bg-gray-50">
                                        <td class="border border-gray-200 px-3 py-2">{{ $row['professional']?->display_name }}</td>
                                        <td class="border border-gray-200 px-3 py-2">{{ $row['total'] }}</td>
                                        <td class="border border-gray-200 px-3 py-2">{{ $row['confirmadas'] }}</td>
                                        <td class="border border-gray-200 px-3 py-2">{{ $row['canceladas'] }}</td>
                                        <td class="border border-gray-200 px-3 py-2">{{ $row['faltas'] }}</td>
                                    </tr>
                                    @break
                                @case('pacientes_lista')
                                    <tr class="odd:bg-white even:bg-gray-50">
                                        <td class="border border-gray-200 px-3 py-2">{{ $row->full_name }}</td>
                                        <td class="border border-gray-200 px-3 py-2">{{ $row->phone ?? $row->cellphone }}</td>
                                        <td class="border border-gray-200 px-3 py-2">{{ $row->address_city }}</td>
                                        <td class="border border-gray-200 px-3 py-2">
                                            {{ $row->last_appointment_at ? \Carbon\Carbon::parse($row->last_appointment_at)->format('d/m/Y') : '-' }}
                                        </td>
                                    </tr>
                                    @break
                                @case('pacientes_frequentes')
                                    <tr class="odd:bg-white even:bg-gray-50">
                                        <td class="border border-gray-200 px-3 py-2">{{ $row->patient?->full_name }}</td>
                                        <td class="border border-gray-200 px-3 py-2">{{ $row->total }}</td>
                                    </tr>
                                    @break
                                @case('pacientes_sem_retorno')
                                    <tr class="odd:bg-white even:bg-gray-50">
                                        <td class="border border-gray-200 px-3 py-2">{{ $row->patient?->full_name }}</td>
                                        <td class="border border-gray-200 px-3 py-2">{{ \Carbon\Carbon::parse($row->last_visit)->format('d/m/Y') }}</td>
                                    </tr>
                                    @break
                                @case('taxa_cancelamento')
                                    <tr class="odd:bg-white even:bg-gray-50">
                                        <td class="border border-gray-200 px-3 py-2">{{ $row['label'] }}</td>
                                        <td class="border border-gray-200 px-3 py-2">{{ $row['total'] }}</td>
                                        <td class="border border-gray-200 px-3 py-2">{{ $row['canceladas'] }}</td>
                                        <td class="border border-gray-200 px-3 py-2">{{ $row['taxa'] }}%</td>
                                    </tr>
                                    @break
                                @case('ticket_medio')
                                    <tr class="odd:bg-white even:bg-gray-50">
                                        <td class="border border-gray-200 px-3 py-2">{{ $row['total'] }}</td>
                                        <td class="border border-gray-200 px-3 py-2">{{ $money($row['total_cents']) }}</td>
                                        <td class="border border-gray-200 px-3 py-2">{{ $money($row['ticket_cents']) }}</td>
                                    </tr>
                                    @break
                                @case('tempo_medio')
                                    <tr class="odd:bg-white even:bg-gray-50">
                                        <td class="border border-gray-200 px-3 py-2">{{ $row->professional?->display_name }}</td>
                                        <td class="border border-gray-200 px-3 py-2">{{ round($row->avg_minutes, 1) }}</td>
                                    </tr>
                                    @break
                            @endswitch
                        @empty
                            <tr>
                                <td colspan="7" class="border border-gray-200 px-4 py-6 text-center text-gray-500">
                                    Nenhum registro encontrado.
                                </td>
                            </tr>
                        @endforelse
                        @if ($report === 'receita')
                            <tr class="bg-gray-50 font-semibold text-gray-800">
                                <td colspan="5" class="border border-gray-200 px-3 py-2 text-right">Total</td>
                                <td class="border border-gray-200 px-3 py-2">{{ $money($summary['total_cents'] ?? 0) }}</td>
                            </tr>
                        @elseif ($report === 'atendimentos')
                            <tr class="bg-gray-50 font-semibold text-gray-800">
                                <td colspan="5" class="border border-gray-200 px-3 py-2 text-right">Total</td>
                                <td class="border border-gray-200 px-3 py-2">{{ $money($sumColumn($rows, 'price_cents')) }}</td>
                            </tr>
                        @elseif ($report === 'contas_receber')
                            <tr class="bg-gray-50 font-semibold text-gray-800">
                                <td colspan="2" class="border border-gray-200 px-3 py-2 text-right">Total</td>
                                <td class="border border-gray-200 px-3 py-2">{{ $money($sumColumn($rows, 'valor_total_cents')) }}</td>
                                <td colspan="3" class="border border-gray-200 px-3 py-2"></td>
                            </tr>
                        @elseif ($report === 'contas_pagar')
                            <tr class="bg-gray-50 font-semibold text-gray-800">
                                <td colspan="2" class="border border-gray-200 px-3 py-2 text-right">Total</td>
                                <td class="border border-gray-200 px-3 py-2">{{ $money($sumColumn($rows, 'valor_cents')) }}</td>
                                <td colspan="2" class="border border-gray-200 px-3 py-2"></td>
                            </tr>
                        @elseif ($report === 'fluxo_caixa')
                            <tr class="bg-gray-50 font-semibold text-gray-800">
                                <td class="border border-gray-200 px-3 py-2 text-right">Total</td>
                                <td class="border border-gray-200 px-3 py-2">{{ $money($sumArrayColumn($rows, 'entrada_cents')) }}</td>
                                <td class="border border-gray-200 px-3 py-2">{{ $money($sumArrayColumn($rows, 'saida_cents')) }}</td>
                                <td class="border border-gray-200 px-3 py-2">{{ $money($sumArrayColumn($rows, 'saldo_cents')) }}</td>
                            </tr>
                        @elseif ($report === 'receita_profissional')
                            <tr class="bg-gray-50 font-semibold text-gray-800">
                                <td colspan="2" class="border border-gray-200 px-3 py-2 text-right">Total</td>
                                <td class="border border-gray-200 px-3 py-2">{{ $money($sumColumn($rows, 'total_cents')) }}</td>
                            </tr>
                        @elseif ($report === 'receita_servico')
                            <tr class="bg-gray-50 font-semibold text-gray-800">
                                <td colspan="2" class="border border-gray-200 px-3 py-2 text-right">Total</td>
                                <td class="border border-gray-200 px-3 py-2">{{ $money($sumColumn($rows, 'total_cents')) }}</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>

        @if (! empty($summary['note']))
            <div class="rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm text-gray-600 shadow-theme-sm">
                {{ $summary['note'] }}
            </div>
        @endif
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelector('[data-print-report]')?.addEventListener('click', () => {
                window.print();
            });
        });
    </script>
</x-app-layout>
