<x-app-layout>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-gray-800">Editar empresa</h2>
    </x-slot>

    <div class="space-y-6">
        <form method="POST" action="{{ route('admin.companies.update', $company) }}" class="rounded-xl border border-gray-200 bg-white p-4 shadow-theme-sm md:p-6">
            @csrf
            @method('PUT')
            @include('companies._form', ['company' => $company])

            <div class="mt-6 flex items-center gap-2">
                <button class="inline-flex items-center justify-center rounded-lg bg-brand-500 px-4 py-2 text-sm font-semibold text-white shadow-theme-xs transition hover:bg-brand-600" type="submit">Salvar</button>
                <a class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-theme-xs transition hover:bg-gray-50" href="{{ route('admin.companies.index') }}">Voltar</a>
            </div>
        </form>

        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-theme-sm md:p-6">
            <h3 class="text-sm font-semibold text-gray-800">Usuarios da empresa</h3>
            <p class="text-sm text-gray-500">Vincule usuarios (nao master) a esta empresa.</p>

            <form method="POST" action="{{ route('admin.companies.users.store', $company) }}" class="mt-4 grid gap-4 md:grid-cols-12">
                @csrf
                <div class="md:col-span-4">
                    <label class="mb-1 block text-sm font-medium text-gray-700" for="user_name">Nome</label>
                    <input id="user_name" name="name" class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 shadow-theme-xs focus:border-brand-300 focus:outline-none focus:ring-3 focus:ring-brand-500/10" value="{{ old('name') }}" required />
                </div>
                <div class="md:col-span-4">
                    <label class="mb-1 block text-sm font-medium text-gray-700" for="user_username">Username</label>
                    <input id="user_username" name="username" class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 shadow-theme-xs focus:border-brand-300 focus:outline-none focus:ring-3 focus:ring-brand-500/10" value="{{ old('username') }}" required />
                </div>
                <div class="md:col-span-4">
                    <label class="mb-1 block text-sm font-medium text-gray-700" for="user_email">Email</label>
                    <input id="user_email" name="email" type="email" class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 shadow-theme-xs focus:border-brand-300 focus:outline-none focus:ring-3 focus:ring-brand-500/10" value="{{ old('email') }}" required />
                </div>
                <div class="md:col-span-4">
                    <label class="mb-1 block text-sm font-medium text-gray-700" for="user_password">Senha</label>
                    <input id="user_password" name="password" type="password" class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 shadow-theme-xs focus:border-brand-300 focus:outline-none focus:ring-3 focus:ring-brand-500/10" required />
                </div>
                <div class="md:col-span-4">
                    <label class="mb-1 block text-sm font-medium text-gray-700" for="role_id">Perfil</label>
                    <select id="role_id" name="role_id" class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 shadow-theme-xs focus:border-brand-300 focus:outline-none focus:ring-3 focus:ring-brand-500/10">
                        <option value="">Sem perfil</option>
                        @foreach ($roles as $role)
                            <option value="{{ $role->id }}">{{ $role->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="md:col-span-12">
                    <button class="inline-flex items-center justify-center rounded-lg bg-brand-500 px-4 py-2 text-sm font-semibold text-white shadow-theme-xs transition hover:bg-brand-600" type="submit">Vincular usuario</button>
                </div>
            </form>

            <div class="mt-6 overflow-x-auto">
                <table class="min-w-full border-separate border border-gray-200 [border-spacing:0] text-sm">
                    <thead class="bg-gray-50">
                        <tr class="text-left text-xs font-semibold uppercase text-gray-500">
                            <th class="border border-gray-200 px-3 py-2">Nome</th>
                            <th class="border border-gray-200 px-3 py-2">Username</th>
                            <th class="border border-gray-200 px-3 py-2">Email</th>
                            <th class="border border-gray-200 px-3 py-2">Perfil</th>
                            <th class="border border-gray-200 px-3 py-2 text-center">Acoes</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($users as $user)
                            <tr class="odd:bg-white even:bg-gray-50">
                                <td class="border border-gray-200 px-3 py-2 font-medium text-gray-800">{{ $user->name }}</td>
                                <td class="border border-gray-200 px-3 py-2 text-gray-600">{{ $user->username }}</td>
                                <td class="border border-gray-200 px-3 py-2 text-gray-600">{{ $user->email }}</td>
                                <td class="border border-gray-200 px-3 py-2 text-gray-600">{{ $user->pivot->role_id ? $roles->firstWhere('id', $user->pivot->role_id)?->name : 'Sem perfil' }}</td>
                                <td class="border border-gray-200 px-3 py-2 text-center">
                                    <form method="POST" action="{{ route('admin.companies.users.destroy', [$company, $user]) }}" onsubmit="return confirm('Remover usuario da empresa?');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="rounded-lg border border-error-500 px-3 py-1 text-xs font-medium text-error-500" type="submit">Excluir</button>
                                    </form>
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
    </div>
</x-app-layout>
