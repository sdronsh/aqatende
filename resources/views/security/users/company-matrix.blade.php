<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-lg font-semibold text-gray-800">Empresas x usuarios</h2>
                <p class="text-sm text-gray-500">Vinculos de usuarios por empresa.</p>
            </div>
            <a class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-theme-xs transition hover:bg-gray-50" href="{{ route('security.users.index') }}">
                Voltar
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

    <div class="space-y-6">
        @forelse ($companies as $company)
            <div class="rounded-xl border border-gray-200 bg-white shadow-theme-sm">
                <div class="border-b border-gray-200 px-4 py-3">
                    <h3 class="text-sm font-semibold text-gray-800">{{ $company->name }}</h3>
                    <p class="text-xs text-gray-500">{{ $company->cnpj }}</p>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full border-separate border border-gray-200 [border-spacing:0] text-sm">
                        <thead class="bg-gray-50">
                            <tr class="text-left text-xs font-semibold uppercase text-gray-500">
                                <th class="border border-gray-200 px-3 py-2">Nome</th>
                                <th class="border border-gray-200 px-3 py-2">Username</th>
                                <th class="border border-gray-200 px-3 py-2">Email</th>
                                <th class="border border-gray-200 px-3 py-2">Perfil</th>
                                <th class="border border-gray-200 px-3 py-2 text-center">Master</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $roles = $company->roles->keyBy('id'); @endphp
                            @forelse ($company->users as $user)
                                <tr class="odd:bg-white even:bg-gray-50">
                                    <td class="border border-gray-200 px-3 py-2 font-medium text-gray-800">{{ $user->name }}</td>
                                    <td class="border border-gray-200 px-3 py-2 text-gray-600">{{ $user->username }}</td>
                                    <td class="border border-gray-200 px-3 py-2 text-gray-600">{{ $user->email }}</td>
                                    <td class="border border-gray-200 px-3 py-2 text-gray-600">
                                        {{ $roles[$user->pivot->role_id]->name ?? 'Sem perfil' }}
                                    </td>
                                    <td class="border border-gray-200 px-3 py-2 text-center text-gray-600">
                                        {{ $user->pivot->is_master ? 'Sim' : 'Nao' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="border border-gray-200 px-4 py-6 text-center text-gray-500">
                                        Nenhum usuario vinculado.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @empty
            <div class="rounded-xl border border-gray-200 bg-white px-4 py-6 text-center text-gray-500 shadow-theme-sm">
                Nenhuma empresa encontrada.
            </div>
        @endforelse
    </div>

    @if (method_exists($companies, 'links'))
        <div class="mt-6 border-t border-gray-200 px-6 py-3">
            {{ $companies->links() }}
        </div>
    @endif
</x-app-layout>
