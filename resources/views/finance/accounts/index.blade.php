<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <div class="text-xs uppercase text-gray-400">Financeiro</div>
                <h2 class="text-lg font-semibold text-gray-800">Contas Bancarias</h2>
            </div>
            <a class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white shadow-theme-xs hover:bg-brand-600" href="{{ route('finance.accounts.create') }}">+ Nova</a>
        </div>
    </x-slot>

    @php
        $formatMoney = fn ($cents) => 'R$ ' . number_format(($cents ?? 0) / 100, 2, ',', '.');
    @endphp

    <div class="rounded-xl border border-gray-200 bg-white shadow-theme-sm">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 px-6 py-4">
            <div class="text-sm font-medium text-gray-700">Lista de contas</div>
            <form method="GET" action="{{ route('finance.accounts.index') }}" class="flex w-full flex-wrap items-center gap-2">
                <input class="w-full flex-1 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 shadow-theme-xs focus:border-brand-300 focus:outline-none focus:ring-3 focus:ring-brand-500/10" name="search" value="{{ request('search') }}" placeholder="Buscar por nome" />
                <select class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 shadow-theme-xs" name="clinic_id">
                    <option value="">Todas as clinicas</option>
                    @foreach ($clinics as $clinic)
                        <option value="{{ $clinic->id }}" @selected((string) $selectedClinicId === (string) $clinic->id)>{{ $clinic->name }}</option>
                    @endforeach
                </select>
                <select class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 shadow-theme-xs" name="unit_id">
                    <option value="">Todas as unidades</option>
                    @foreach ($units as $unit)
                        <option value="{{ $unit->id }}" @selected((string) $selectedUnitId === (string) $unit->id)>{{ $unit->name }}</option>
                    @endforeach
                </select>
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
                        <th class="border border-gray-200 px-4 py-3">Nome</th>
                        <th class="border border-gray-200 px-4 py-3">Clinica</th>
                        <th class="border border-gray-200 px-4 py-3">Unidade</th>
                        <th class="border border-gray-200 px-4 py-3">Tipo</th>
                        <th class="border border-gray-200 px-4 py-3">Saldo inicial</th>
                        <th class="border border-gray-200 px-4 py-3">Status</th>
                        <th class="border border-gray-200 px-4 py-3 text-right">Acoes</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($accounts as $account)
                        <tr class="odd:bg-gray-50">
                            <td class="border border-gray-200 px-4 py-3 font-medium text-gray-800">{{ $account->name }}</td>
                            <td class="border border-gray-200 px-4 py-3 text-gray-600">{{ $account->clinic?->name ?? '-' }}</td>
                            <td class="border border-gray-200 px-4 py-3 text-gray-600">{{ $account->unit?->name ?? '-' }}</td>
                            <td class="border border-gray-200 px-4 py-3 text-gray-600">{{ ucfirst($account->type) }}</td>
                            <td class="border border-gray-200 px-4 py-3 text-gray-600">{{ $formatMoney($account->initial_balance_cents) }}</td>
                            <td class="border border-gray-200 px-4 py-3 text-gray-600">{{ $account->active ? 'Ativa' : 'Inativa' }}</td>
                            <td class="border border-gray-200 px-4 py-3">
                                <div class="flex justify-end gap-2">
                                    <a class="rounded-lg border border-brand-500 px-2 py-1 text-xs font-medium text-brand-500 hover:bg-brand-50" href="{{ route('finance.accounts.edit', $account) }}">Editar</a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="border border-gray-200 px-6 py-6 text-center text-gray-500">Nenhuma conta cadastrada.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if (method_exists($accounts, 'links'))
            <div class="border-t border-gray-200 px-6 py-3">
                {{ $accounts->links() }}
            </div>
        @endif
    </div>
</x-app-layout>
