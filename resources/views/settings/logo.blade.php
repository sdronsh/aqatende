<x-app-layout>
    <x-slot name="header">
        <div>
            <div class="text-xs uppercase text-gray-400">Configuracoes</div>
            <h2 class="text-lg font-semibold text-gray-800">Logo da empresa</h2>
        </div>
    </x-slot>

    <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-theme-sm">
        <div class="flex flex-wrap items-center gap-6">
            <div class="flex h-24 w-24 items-center justify-center overflow-hidden rounded-lg border border-gray-200 bg-gray-50">
                @if ($logoPath)
                    <img class="h-20 w-20 object-contain" src="{{ asset('storage/'.$logoPath) }}" alt="Logo atual" />
                @else
                    <span class="text-xs text-gray-400">Sem logo</span>
                @endif
            </div>
            <div class="flex-1">
                <div class="text-sm font-semibold text-gray-800">{{ $company->name }}</div>
                @php
                    $cnpjDigits = preg_replace('/\D/', '', (string) $company->cnpj);
                    $cnpjFormatted = strlen($cnpjDigits) === 14
                        ? substr($cnpjDigits, 0, 2).'.'.substr($cnpjDigits, 2, 3).'.'.substr($cnpjDigits, 5, 3).'/'.substr($cnpjDigits, 8, 4).'-'.substr($cnpjDigits, 12, 2)
                        : $company->cnpj;
                @endphp
                <div class="text-xs text-gray-500">CNPJ: {{ $cnpjFormatted }}</div>
            </div>
        </div>

        <form method="POST" action="{{ route('settings.logo.update') }}" enctype="multipart/form-data" class="mt-6 space-y-4">
            @csrf
            @method('PUT')
            <input type="hidden" name="company_id" value="{{ $company->id }}" />

            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700" for="logo">Upload da logo</label>
                <input class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 shadow-theme-xs focus:border-brand-300 focus:outline-none focus:ring-3 focus:ring-brand-500/10" type="file" id="logo" name="logo" accept="image/png,image/jpeg,image/svg+xml" />
                <p class="mt-1 text-xs text-gray-500">Formato PNG, JPG ou SVG. Tamanho maximo 3MB.</p>
            </div>

            <div class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-800">
                A logo sera aplicada na empresa ativa desta sessao: <strong>{{ $company->name }}</strong>.
            </div>

            <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                <input type="checkbox" class="h-4 w-4 rounded border-gray-300 text-brand-500" name="remove_logo" value="1" />
                Remover logo atual
            </label>

            <div class="flex items-center gap-2">
                <button type="submit" class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-semibold text-white shadow-theme-xs hover:bg-brand-600">Salvar</button>
                <a class="rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-theme-xs hover:bg-gray-50" href="{{ route('dashboard') }}">Voltar</a>
            </div>
        </form>
    </div>
</x-app-layout>
