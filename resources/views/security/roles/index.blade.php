<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-lg font-semibold text-gray-800">Perfis de acesso</h2>
                <p class="text-sm text-gray-500">Defina permissoes por modulo e acao.</p>
            </div>
            <a class="inline-flex items-center justify-center rounded-lg bg-brand-500 px-4 py-2 text-sm font-semibold text-white shadow-theme-xs transition hover:bg-brand-600" href="{{ route('security.roles.create') }}">
                + Novo
            </a>
        </div>
    </x-slot>

    <div class="mb-4 rounded-xl border border-gray-200 bg-white p-4 shadow-theme-sm">
        <form method="GET" class="flex flex-wrap items-center gap-2">
            <select class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 shadow-theme-xs focus:border-brand-300 focus:outline-none focus:ring-3 focus:ring-brand-500/10" name="per_page">
                @php $selectedPerPage = $perPage ?? request('per_page', '10'); @endphp
                <option value="10" @selected($selectedPerPage === '10')>10</option>
                <option value="20" @selected($selectedPerPage === '20')>20</option>
                <option value="50" @selected($selectedPerPage === '50')>50</option>
                <option value="all" @selected($selectedPerPage === 'all')>Todos</option>
            </select>
            <button class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-theme-xs transition hover:bg-gray-50" type="submit">Aplicar</button>
        </form>
    </div>

    <div class="rounded-xl border border-gray-200 bg-white shadow-theme-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full border-separate border border-gray-200 [border-spacing:0] text-sm">
                <thead class="bg-gray-50">
                    <tr class="text-left text-xs font-semibold uppercase text-gray-500">
                        <th class="border border-gray-200 px-3 py-2">Nome</th>
                        <th class="border border-gray-200 px-3 py-2">Descricao</th>
                        <th class="border border-gray-200 px-3 py-2 text-center">Padrao</th>
                        <th class="border border-gray-200 px-3 py-2 text-center">Acoes</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($roles as $role)
                        <tr class="odd:bg-white even:bg-gray-50">
                            <td class="border border-gray-200 px-3 py-2 font-medium text-gray-800">{{ $role->name }}</td>
                            <td class="border border-gray-200 px-3 py-2 text-gray-600">{{ $role->description }}</td>
                            <td class="border border-gray-200 px-3 py-2 text-center text-gray-600">
                                {{ $role->is_default ? 'Sim' : 'Nao' }}
                            </td>
                            <td class="border border-gray-200 px-3 py-2">
                                <div class="flex items-center justify-center gap-2">
                                    <a class="rounded-lg border border-brand-500 px-3 py-1 text-xs font-medium text-brand-500" href="{{ route('security.roles.edit', $role) }}">Editar</a>
                                    <form method="POST" action="{{ route('security.roles.destroy', $role) }}" onsubmit="return confirm('Remover este perfil?');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="rounded-lg border border-error-500 px-3 py-1 text-xs font-medium text-error-500" type="submit">Excluir</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="border border-gray-200 px-4 py-6 text-center text-gray-500">
                                Nenhum perfil cadastrado.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if (method_exists($roles, 'links'))
            <div class="border-t border-gray-200 px-6 py-3">
                {{ $roles->links() }}
            </div>
        @endif
    </div>
</x-app-layout>
