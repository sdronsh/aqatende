<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <div class="text-xs uppercase text-gray-400">Cadastro</div>
                <h2 class="text-lg font-semibold text-gray-800">Servicos</h2>
            </div>
            <a class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white shadow-theme-xs hover:bg-brand-600" href="{{ route('services.create') }}">+ Novo</a>
        </div>
    </x-slot>

    <div class="rounded-xl border border-gray-200 bg-white shadow-theme-sm">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 px-6 py-4">
            <div class="text-sm font-medium text-gray-700">Lista de Servicos</div>
            <form method="GET" action="{{ route('services.index') }}" class="flex w-full max-w-3xl flex-wrap items-center gap-2">
                <input class="w-full flex-1 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 shadow-theme-xs focus:border-brand-300 focus:outline-none focus:ring-3 focus:ring-brand-500/10" name="search" value="{{ request('search') }}" placeholder="Buscar por nome ou clinica" />
                <select class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 shadow-theme-xs focus:border-brand-300 focus:outline-none focus:ring-3 focus:ring-brand-500/10" name="per_page">
                    @php $selectedPerPage = $perPage ?? request('per_page', '10'); @endphp
                    <option value="10" @selected($selectedPerPage === '10')>10</option>
                    <option value="20" @selected($selectedPerPage === '20')>20</option>
                    <option value="50" @selected($selectedPerPage === '50')>50</option>
                    <option value="all" @selected($selectedPerPage === 'all')>Todos</option>
                </select>
                <button class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-theme-xs hover:bg-gray-50" type="submit">Buscar</button>
            </form>
        </div>
        <div class="responsive-table-wrapper overflow-x-auto">
            <table class="responsive-table min-w-full border-separate border border-gray-200 [border-spacing:0] text-sm">
                <thead class="bg-gray-50">
                    <tr class="text-left text-xs font-semibold uppercase text-gray-500">
                        <th class="border border-gray-200 px-4 py-3">Nome</th>
                        <th class="border border-gray-200 px-4 py-3">Clinica</th>
                        <th class="border border-gray-200 px-4 py-3">Unidade</th>
                        <th class="border border-gray-200 px-4 py-3">Duracao</th>
                        <th class="border border-gray-200 px-4 py-3">Preco</th>
                        <th class="border border-gray-200 px-4 py-3">Tipo</th>
                        <th class="border border-gray-200 px-4 py-3 text-right">Acoes</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($services as $service)
                        <tr class="odd:bg-gray-50">
                            <td class="border border-gray-200 px-4 py-3 font-medium text-gray-800" data-label="Nome">{{ $service->name }}</td>
                            <td class="border border-gray-200 px-4 py-3 text-gray-600" data-label="Clinica">{{ $service->clinic?->name }}</td>
                            <td class="border border-gray-200 px-4 py-3 text-gray-600" data-label="Unidade">{{ $service->unit?->name ?? '-' }}</td>
                            <td class="border border-gray-200 px-4 py-3 text-gray-600" data-label="Duracao">{{ $service->duration_minutes }} min</td>
                            <td class="border border-gray-200 px-4 py-3 text-gray-600" data-label="Preco">
                                R$ {{ number_format(($service->price_cents ?? 0) / 100, 2, ',', '.') }}
                            </td>
                            <td class="border border-gray-200 px-4 py-3 text-gray-600" data-label="Tipo">
                                @if ($service->is_package)
                                    <span class="rounded-full bg-brand-50 px-2 py-1 text-xs font-semibold text-brand-600">Pacote</span>
                                    @if ($service->packageItems->isNotEmpty())
                                        <span class="mt-1 block text-xs text-gray-400">{{ $service->packageItems->pluck('name')->join(' + ') }}</span>
                                    @endif
                                @elseif ($service->shared_service)
                                    <span class="rounded-full bg-brand-50 px-2 py-1 text-xs font-semibold text-brand-600">Compartilhado</span>
                                @else
                                    <span class="text-gray-400">Individual</span>
                                @endif
                            </td>
                            <td class="border border-gray-200 px-4 py-3" data-actions>
                                <div class="flex justify-end gap-2">
                                    <a class="rounded-lg border border-brand-500 px-2 py-1 text-xs font-medium text-brand-500 hover:bg-brand-50" href="{{ route('services.edit', $service) }}">Editar</a>
                                    <form method="POST" action="{{ route('services.destroy', $service) }}" onsubmit="return confirm('Remover servico?');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="rounded-lg border border-error-500 px-2 py-1 text-xs font-medium text-error-500 hover:bg-error-50" type="submit">Excluir</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="border border-gray-200 px-6 py-6 text-center text-gray-500" data-empty>Nenhum servico cadastrado.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if (method_exists($services, 'links'))
            <div class="border-t border-gray-200 px-6 py-3">
                {{ $services->links() }}
            </div>
        @endif
    </div>
</x-app-layout>
