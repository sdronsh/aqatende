<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <div class="text-xs uppercase text-gray-400">Financeiro</div>
                <h2 class="text-lg font-semibold text-gray-800">Contas a Receber</h2>
            </div>
            <a class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white shadow-theme-xs hover:bg-brand-600" href="{{ route('finance.receivables.create') }}">+ Nova</a>
        </div>
    </x-slot>

    @php
        $formatMoney = fn ($cents) => 'R$ ' . number_format(($cents ?? 0) / 100, 2, ',', '.');
        $selectedStatus = request('status', '');
    @endphp

    <div class="rounded-xl border border-gray-200 bg-white shadow-theme-sm">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 px-6 py-4">
            <div class="text-sm font-medium text-gray-700">Lista de contas</div>
            <form method="GET" action="{{ route('finance.receivables.index') }}" class="flex w-full flex-wrap items-center gap-2">
                <input class="w-full flex-1 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 shadow-theme-xs focus:border-brand-300 focus:outline-none focus:ring-3 focus:ring-brand-500/10" name="search" value="{{ request('search') }}" placeholder="Buscar por descricao ou cliente" />
                <select class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 shadow-theme-xs" name="clinic_id">
                    <option value="">Todas as clinicas</option>
                    @foreach ($clinics as $clinic)
                        <option value="{{ $clinic->id }}" @selected((string) $selectedClinicId === (string) $clinic->id)>{{ $clinic->name }}</option>
                    @endforeach
                </select>
                <select class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 shadow-theme-xs" name="status">
                    <option value="">Todos os status</option>
                    <option value="aberto" @selected($selectedStatus === 'aberto')>Aberto</option>
                    <option value="pago" @selected($selectedStatus === 'pago')>Pago</option>
                    <option value="atrasado" @selected($selectedStatus === 'atrasado')>Atrasado</option>
                    <option value="cancelado" @selected($selectedStatus === 'cancelado')>Cancelado</option>
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
                        <th class="border border-gray-200 px-4 py-3">Descricao</th>
                        <th class="border border-gray-200 px-4 py-3">Cliente</th>
                        <th class="border border-gray-200 px-4 py-3">Profissional</th>
                        <th class="border border-gray-200 px-4 py-3">Clinica</th>
                        <th class="border border-gray-200 px-4 py-3">Vencimento</th>
                        <th class="border border-gray-200 px-4 py-3">Valor</th>
                        <th class="border border-gray-200 px-4 py-3">Status</th>
                        <th class="border border-gray-200 px-4 py-3">Forma</th>
                        <th class="border border-gray-200 px-4 py-3 text-right">Acoes</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($receivables as $receivable)
                        <tr class="odd:bg-gray-50">
                            <td class="border border-gray-200 px-4 py-3 font-medium text-gray-800">{{ $receivable->descricao }}</td>
                            <td class="border border-gray-200 px-4 py-3 text-gray-600">{{ $receivable->patient?->full_name ?? '-' }}</td>
                            <td class="border border-gray-200 px-4 py-3 text-gray-600">{{ $receivable->professional?->display_name ?? '-' }}</td>
                            <td class="border border-gray-200 px-4 py-3 text-gray-600">{{ $receivable->clinic?->name ?? '-' }}</td>
                            <td class="border border-gray-200 px-4 py-3 text-gray-600">{{ optional($receivable->data_vencimento)->format('d/m/Y') }}</td>
                            <td class="border border-gray-200 px-4 py-3 text-gray-600">{{ $formatMoney($receivable->valor_total_cents) }}</td>
                            <td class="border border-gray-200 px-4 py-3 text-gray-600">{{ ucfirst($receivable->status) }}</td>
                            <td class="border border-gray-200 px-4 py-3 text-gray-600">{{ $receivable->forma_pagamento ? ucfirst($receivable->forma_pagamento) : '-' }}</td>
                            <td class="border border-gray-200 px-4 py-3">
                                <div class="flex justify-end gap-2">
                                    <a class="rounded-lg border border-brand-500 px-2 py-1 text-xs font-medium text-brand-500 hover:bg-brand-50" href="{{ route('finance.receivables.edit', $receivable) }}">Editar</a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="border border-gray-200 px-6 py-6 text-center text-gray-500">Nenhuma conta registrada.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if (method_exists($receivables, 'links'))
            <div class="border-t border-gray-200 px-6 py-3">
                {{ $receivables->links() }}
            </div>
        @endif
    </div>
</x-app-layout>
