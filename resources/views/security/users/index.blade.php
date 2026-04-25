<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-lg font-semibold text-gray-800">Usuarios</h2>
                <p class="text-sm text-gray-500">Acesso dos usuarios vinculados a empresa.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-theme-xs transition hover:bg-gray-50" href="{{ route('security.users.matrix') }}">
                    Empresas x usuarios
                </a>
                <a class="inline-flex items-center justify-center rounded-lg bg-brand-500 px-4 py-2 text-sm font-semibold text-white shadow-theme-xs transition hover:bg-brand-600" href="{{ route('security.users.create') }}">
                    + Novo
                </a>
            </div>
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
                        <th class="border border-gray-200 px-3 py-2">Username</th>
                        <th class="border border-gray-200 px-3 py-2">Email</th>
                        <th class="border border-gray-200 px-3 py-2">Perfil</th>
                        <th class="border border-gray-200 px-3 py-2 text-center">Master</th>
                        <th class="border border-gray-200 px-3 py-2 text-center">Acoes</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $user)
                        <tr class="odd:bg-white even:bg-gray-50">
                            <td class="border border-gray-200 px-3 py-2 font-medium text-gray-800">{{ $user->name }}</td>
                            <td class="border border-gray-200 px-3 py-2 text-gray-600">{{ $user->username }}</td>
                            <td class="border border-gray-200 px-3 py-2 text-gray-600">{{ $user->email }}</td>
                            <td class="border border-gray-200 px-3 py-2 text-gray-600">
                                {{ $roles[$user->role_id]->name ?? 'Sem perfil' }}
                            </td>
                            <td class="border border-gray-200 px-3 py-2 text-center text-gray-600">
                                {{ $user->is_master ? 'Sim' : 'Nao' }}
                            </td>
                            <td class="border border-gray-200 px-3 py-2">
                                <div class="flex items-center justify-center gap-2">
                                    <a class="rounded-lg border border-brand-500 px-3 py-1 text-xs font-medium text-brand-500" href="{{ route('security.users.edit', $user) }}">Editar</a>
                                    <form method="POST" action="{{ route('security.users.destroy', $user) }}" onsubmit="return confirm('Remover este usuario?');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="rounded-lg border border-error-500 px-3 py-1 text-xs font-medium text-error-500" type="submit">Excluir</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                                <td colspan="6" class="border border-gray-200 px-4 py-6 text-center text-gray-500">
                                    Nenhum usuario cadastrado.
                                </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if (method_exists($users, 'links'))
            <div class="border-t border-gray-200 px-6 py-3">
                {{ $users->links() }}
            </div>
        @endif
    </div>
</x-app-layout>
