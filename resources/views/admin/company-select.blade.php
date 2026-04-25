<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-lg font-semibold text-gray-800">Selecionar empresa</h2>
                <p class="text-sm text-gray-500">Escolha a empresa para iniciar a sessão.</p>
            </div>
        </div>
    </x-slot>

    <div class="mb-4 rounded-xl border border-gray-200 bg-white p-4 shadow-theme-sm">
        <form method="GET" class="flex flex-wrap items-center gap-2">
            <input class="w-full flex-1 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 shadow-theme-xs focus:border-brand-300 focus:outline-none focus:ring-3 focus:ring-brand-500/10" type="text" name="search" value="{{ $search ?? '' }}" placeholder="Buscar por nome, CNPJ ou email" />
            <button class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-theme-xs transition hover:bg-gray-50" type="submit">Buscar</button>
        </form>
    </div>

    <div class="rounded-xl border border-gray-200 bg-white shadow-theme-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full border-separate border border-gray-200 [border-spacing:0] text-sm">
                <thead class="bg-gray-50">
                    <tr class="text-left text-xs font-semibold uppercase text-gray-500">
                        <th class="border border-gray-200 px-3 py-2">Empresa</th>
                        <th class="border border-gray-200 px-3 py-2">CNPJ</th>
                        <th class="border border-gray-200 px-3 py-2">Email</th>
                        <th class="border border-gray-200 px-3 py-2 text-center">Entrar</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($companies as $company)
                        <tr class="odd:bg-white even:bg-gray-50">
                            <td class="border border-gray-200 px-3 py-2 font-medium text-gray-800">{{ $company->name }}</td>
                            <td class="border border-gray-200 px-3 py-2 text-gray-600">{{ $company->cnpj }}</td>
                            <td class="border border-gray-200 px-3 py-2 text-gray-600">{{ $company->email }}</td>
                            <td class="border border-gray-200 px-3 py-2 text-center">
                                <form method="POST" action="{{ route('admin.company-select.store') }}">
                                    @csrf
                                    <input type="hidden" name="company_id" value="{{ $company->id }}" />
                                    <button class="rounded-lg bg-brand-500 px-3 py-1 text-xs font-semibold text-white shadow-theme-xs transition hover:bg-brand-600" type="submit">Selecionar</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="border border-gray-200 px-4 py-6 text-center text-gray-500">
                                Nenhuma empresa encontrada.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
