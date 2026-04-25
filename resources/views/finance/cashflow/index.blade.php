<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <div class="text-xs uppercase text-gray-400">Financeiro</div>
                <h2 class="text-lg font-semibold text-gray-800">Fluxo de Caixa</h2>
            </div>
        </div>
    </x-slot>

    @php
        $formatMoney = fn ($cents) => 'R$ ' . number_format(($cents ?? 0) / 100, 2, ',', '.');
        $selectedTipo = request('tipo', '');
    @endphp

    <div class="rounded-xl border border-gray-200 bg-white shadow-theme-sm">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 px-6 py-4">
            <div class="text-sm font-medium text-gray-700">Movimentacoes</div>
            <form method="GET" action="{{ route('finance.cashflow.index') }}" class="flex w-full flex-wrap items-center gap-2">
                <input class="w-full flex-1 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 shadow-theme-xs focus:border-brand-300 focus:outline-none focus:ring-3 focus:ring-brand-500/10" name="search" value="{{ request('search') }}" placeholder="Buscar por descricao" />
                <select class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 shadow-theme-xs" name="clinic_id">
                    <option value="">Todas as clinicas</option>
                    @foreach ($clinics as $clinic)
                        <option value="{{ $clinic->id }}" @selected((string) $selectedClinicId === (string) $clinic->id)>{{ $clinic->name }}</option>
                    @endforeach
                </select>
                <select class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 shadow-theme-xs" name="tipo">
                    <option value="">Todos os tipos</option>
                    <option value="entrada" @selected($selectedTipo === 'entrada')>Entrada</option>
                    <option value="saida" @selected($selectedTipo === 'saida')>Saida</option>
                </select>
                <select class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 shadow-theme-xs" name="professional_id">
                    <option value="">Todos os profissionais</option>
                    @foreach ($professionals as $professional)
                        <option value="{{ $professional->id }}" @selected(request('professional_id') == $professional->id)>{{ $professional->display_name }}</option>
                    @endforeach
                </select>
                <input class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 shadow-theme-xs" type="date" name="date_from" value="{{ request('date_from') }}" />
                <input class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 shadow-theme-xs" type="date" name="date_to" value="{{ request('date_to') }}" />
                <select class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 shadow-theme-xs" name="per_page">
                    @php $selectedPerPage = $perPage ?? request('per_page', '10'); @endphp
                    <option value="10" @selected($selectedPerPage === '10')>10</option>
                    <option value="20" @selected($selectedPerPage === '20')>20</option>
                    <option value="50" @selected($selectedPerPage === '50')>50</option>
                    <option value="all" @selected($selectedPerPage === 'all')>Todos</option>
                </select>
                <button class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-theme-xs hover:bg-gray-50" type="submit">Buscar</button>
            </form>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full border-separate border border-gray-200 [border-spacing:0] text-sm">
                <thead class="bg-gray-50">
                    <tr class="text-left text-xs font-semibold uppercase text-gray-500">
                        <th class="border border-gray-200 px-4 py-3">Data</th>
                        <th class="border border-gray-200 px-4 py-3">Tipo</th>
                        <th class="border border-gray-200 px-4 py-3">Descricao</th>
                        <th class="border border-gray-200 px-4 py-3">Categoria</th>
                        <th class="border border-gray-200 px-4 py-3">Conta</th>
                        <th class="border border-gray-200 px-4 py-3">Profissional</th>
                        <th class="border border-gray-200 px-4 py-3">Usuario</th>
                        <th class="border border-gray-200 px-4 py-3">Valor</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($entries as $entry)
                        <tr class="odd:bg-gray-50">
                            <td class="border border-gray-200 px-4 py-3 text-gray-600">{{ optional($entry->data_movimento)->format('d/m/Y H:i') }}</td>
                            <td class="border border-gray-200 px-4 py-3 text-gray-600">{{ ucfirst($entry->tipo) }}</td>
                            <td class="border border-gray-200 px-4 py-3 text-gray-600">{{ $entry->descricao ?? '-' }}</td>
                            <td class="border border-gray-200 px-4 py-3 text-gray-600">{{ $entry->category?->name ?? '-' }}</td>
                            <td class="border border-gray-200 px-4 py-3 text-gray-600">{{ $entry->account?->name ?? '-' }}</td>
                            <td class="border border-gray-200 px-4 py-3 text-gray-600">{{ $entry->professional?->display_name ?? '-' }}</td>
                            <td class="border border-gray-200 px-4 py-3 text-gray-600">{{ $entry->user?->name ?? '-' }}</td>
                            <td class="border border-gray-200 px-4 py-3 text-gray-600">{{ $formatMoney($entry->valor_cents) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="border border-gray-200 px-6 py-6 text-center text-gray-500">Nenhuma movimentacao registrada.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if (method_exists($entries, 'links'))
            <div class="border-t border-gray-200 px-6 py-3">
                {{ $entries->links() }}
            </div>
        @endif
    </div>
</x-app-layout>
