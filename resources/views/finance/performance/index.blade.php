<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <div class="text-xs uppercase text-gray-400">Financeiro</div>
                <h2 class="text-lg font-semibold text-gray-800">Desempenho</h2>
            </div>
            <a class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white shadow-theme-xs hover:bg-brand-600" href="{{ route('finance.receivables.create') }}">+ Receita</a>
        </div>
    </x-slot>

    @php
        $formatMoney = fn ($cents) => 'R$ ' . number_format(($cents ?? 0) / 100, 2, ',', '.');
        $formatPercent = fn ($value) => $value === null ? '-' : number_format($value, 1, ',', '.') . '%';
        $changeClass = fn ($value, $invert = false) => $value === null || $value == 0
            ? 'text-gray-400'
            : (($invert ? $value < 0 : $value > 0) ? 'text-emerald-600' : 'text-error-500');
        $periodLabel = $month->isSameMonth(now()) && ! $includeFuture
            ? 'Mes atual - De ' . $from->format('d/m') . ' a ' . $to->format('d/m')
            : ucfirst($month->locale('pt_BR')->translatedFormat('F \\d\\e Y'));
        $colors = ['#1597e5', '#10d39a', '#f7c948', '#f43f75', '#bd5ce6'];
        $gradientParts = [];
        $offset = 0;
        foreach ($topServices as $index => $service) {
            $next = $offset + (int) $service['percent'];
            $gradientParts[] = ($colors[$index] ?? '#94a3b8') . ' ' . $offset . '% ' . $next . '%';
            $offset = $next;
        }
        if ($offset < 100 && $topServices->isNotEmpty()) {
            $gradientParts[] = ($colors[max(0, $topServices->count() - 1)] ?? '#94a3b8') . ' ' . $offset . '% 100%';
        }
        $pieGradient = $topServices->isEmpty() ? '#f3f4f6 0% 100%' : implode(', ', $gradientParts);
    @endphp

    <div class="space-y-4">
        <div class="rounded-xl border border-gray-200 bg-white shadow-theme-sm">
            <form method="GET" action="{{ route('finance.performance.index') }}" class="flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <div class="text-sm font-semibold text-gray-800">{{ $periodLabel }}</div>
                    <div class="text-xs text-gray-400">{{ $from->format('d/m/Y') }} ate {{ $to->format('d/m/Y') }}</div>
                </div>
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                    <input class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 shadow-theme-xs focus:border-brand-300 focus:outline-none focus:ring-3 focus:ring-brand-500/10" type="month" name="month" value="{{ $month->format('Y-m') }}">
                    <label class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-600 shadow-theme-xs">
                        <input type="checkbox" name="include_future" value="1" class="rounded border-gray-300 text-brand-500 focus:ring-brand-500" @checked($includeFuture)>
                        dados futuros
                    </label>
                    <button class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-semibold text-white shadow-theme-xs hover:bg-brand-600" type="submit">Aplicar</button>
                </div>
            </form>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-theme-sm">
            <div class="mb-4 text-sm font-semibold text-gray-700">Balanco financeiro no periodo</div>
            <div class="grid gap-3 sm:grid-cols-3">
                <div class="rounded-lg bg-emerald-50 p-4 text-center">
                    <div class="text-xl font-semibold text-emerald-600">{{ $formatMoney($revenueCents) }}</div>
                    <div class="text-sm text-gray-500">receita</div>
                    <div class="mt-2 text-xs font-semibold {{ $changeClass($revenueChange) }}">{{ $formatPercent($revenueChange) }}</div>
                </div>
                <div class="rounded-lg bg-error-50 p-4 text-center">
                    <div class="text-xl font-semibold text-error-600">{{ $formatMoney($expenseCents) }}</div>
                    <div class="text-sm text-gray-500">gastos</div>
                    <div class="mt-2 text-xs font-semibold {{ $changeClass($expenseChange, true) }}">{{ $formatPercent($expenseChange) }}</div>
                </div>
                <div class="rounded-lg bg-brand-50 p-4 text-center">
                    <div class="text-xl font-semibold text-brand-700">{{ $formatMoney($profitCents) }}</div>
                    <div class="text-sm text-gray-500">lucro</div>
                    <div class="mt-2 text-xs font-semibold {{ $changeClass($profitChange) }}">{{ $formatPercent($profitChange) }}</div>
                </div>
            </div>
            <div class="mt-3 text-center text-xs text-gray-400">contra periodo equivalente do mes anterior</div>
        </div>

        <div class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_minmax(360px,0.8fr)]">
            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-theme-sm">
                <div class="mb-4 text-sm font-semibold text-gray-700">Receita por forma de pagamento</div>
                <div class="grid gap-3 sm:grid-cols-5">
                    @foreach ($paymentMethods as $method)
                        <div class="rounded-lg border border-gray-100 bg-white p-3 text-center">
                            <div class="mx-auto flex h-10 w-10 items-center justify-center rounded-full bg-emerald-50 text-xs font-bold text-emerald-600">{{ $method['icon'] }}</div>
                            <div class="mt-2 text-xs font-medium text-gray-600">{{ $method['label'] }}</div>
                            <div class="text-xs text-gray-500">{{ $formatMoney($method['total_cents']) }}</div>
                            <div class="mt-1 text-sm font-semibold text-emerald-600">{{ number_format($method['percent'], 1, ',', '.') }}%</div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-theme-sm">
                <div class="mb-4 text-sm font-semibold text-gray-700">Resumo esforco no periodo</div>
                <div class="grid gap-4">
                    <div class="flex items-center gap-3">
                        <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-brand-50 text-xl font-bold text-brand-600">D</div>
                        <div><span class="text-2xl font-semibold text-brand-600">{{ $effort['days'] }}</span> <span class="text-sm text-gray-500">dias</span></div>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-brand-50 text-xl font-bold text-brand-600">A</div>
                        <div><span class="text-2xl font-semibold text-brand-600">{{ $effort['appointments'] }}</span> <span class="text-sm text-gray-500">atendimentos</span></div>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-brand-50 text-xl font-bold text-brand-600">C</div>
                        <div><span class="text-2xl font-semibold text-brand-600">{{ $effort['patients'] }}</span> <span class="text-sm text-gray-500">clientes atendidos</span></div>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-brand-50 text-xl font-bold text-brand-600">H</div>
                        <div><span class="text-2xl font-semibold text-brand-600">{{ $effort['hours'] ?? '-' }}</span> <span class="text-sm text-gray-500">horas atendidas</span></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-theme-sm">
            <div class="text-center">
                <div class="text-lg font-semibold text-gray-800">Top 5 Receita por servico</div>
                <div class="text-sm text-gray-400">Esta e apenas uma projecao</div>
            </div>

            @if ($topServices->isEmpty())
                <div class="py-16 text-center text-sm text-gray-500">Nenhum dado encontrado para a selecao.</div>
            @else
                <div class="mt-6 grid gap-6 lg:grid-cols-[320px_minmax(0,1fr)] lg:items-center">
                    <div class="mx-auto flex h-64 w-64 items-center justify-center rounded-full shadow-inner" style="background: conic-gradient({{ $pieGradient }});">
                        <div class="flex h-28 w-28 items-center justify-center rounded-full bg-white text-sm font-semibold text-gray-500 shadow-theme-sm">
                            Servicos
                        </div>
                    </div>
                    <div class="space-y-3">
                        @foreach ($topServices as $index => $service)
                            <div class="flex items-start gap-3">
                                <span class="mt-1 h-3 w-3 shrink-0 rounded-full" style="background-color: {{ $colors[$index] ?? '#94a3b8' }}"></span>
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center justify-between gap-3">
                                        <div class="truncate text-sm font-semibold text-gray-800">{{ $service['name'] }} ({{ $service['quantity'] }})</div>
                                        <div class="text-sm font-semibold text-gray-600">{{ $service['percent'] }}%</div>
                                    </div>
                                    <div class="text-sm text-gray-400">Receita: {{ $formatMoney($service['total_cents']) }}</div>
                                    <div class="mt-1 h-2 overflow-hidden rounded-full bg-gray-100">
                                        <div class="h-full rounded-full" style="width: {{ $service['percent'] }}%; background-color: {{ $colors[$index] ?? '#94a3b8' }}"></div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
