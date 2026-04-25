<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <div class="text-xs uppercase text-gray-400">Financeiro</div>
                <h2 class="text-lg font-semibold text-gray-800">Editar categoria</h2>
            </div>
        </div>
    </x-slot>

    <form method="POST" action="{{ route('finance.categories.update', $category) }}" class="rounded-xl border border-gray-200 bg-white p-6 shadow-theme-sm space-y-4">
        @csrf
        @method('PUT')
        @include('finance/categories/_form', [
            'category' => $category,
            'clinics' => $clinics,
        ])

        <div class="flex items-center justify-end gap-2 border-t border-gray-100 pt-4">
            <a class="rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-theme-xs hover:bg-gray-50" href="{{ route('finance.categories.index') }}">Voltar</a>
            <button class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-semibold text-white shadow-theme-xs hover:bg-brand-600" type="submit">Salvar</button>
        </div>
    </form>
</x-app-layout>
