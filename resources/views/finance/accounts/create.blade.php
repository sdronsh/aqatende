<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <div class="text-xs uppercase text-gray-400">Financeiro</div>
                <h2 class="text-lg font-semibold text-gray-800">Nova conta bancaria</h2>
            </div>
        </div>
    </x-slot>

    <form method="POST" action="{{ route('finance.accounts.store') }}" class="rounded-xl border border-gray-200 bg-white p-6 shadow-theme-sm space-y-4">
        @csrf
        @include('finance/accounts/_form', [
            'account' => null,
            'clinics' => $clinics,
            'units' => $units,
        ])

        <div class="flex items-center justify-end gap-2 border-t border-gray-100 pt-4">
            <a class="rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-theme-xs hover:bg-gray-50" href="{{ route('finance.accounts.index') }}">Voltar</a>
            <button class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-semibold text-white shadow-theme-xs hover:bg-brand-600" type="submit">Salvar</button>
        </div>
    </form>
</x-app-layout>
