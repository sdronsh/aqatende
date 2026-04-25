<x-app-layout>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-gray-800">Nova clinica</h2>
    </x-slot>

    <form method="POST" action="{{ route('clinics.store') }}">
        @csrf
        <div class="rounded-xl border border-gray-200 bg-white shadow-theme-sm">
            <div class="p-4 md:p-6">
                @include('clinics._form', ['clinic' => new \App\Models\Clinic(), 'specialties' => $specialties])
            </div>
            <div class="flex flex-wrap items-center gap-2 border-t border-gray-100 bg-gray-50 px-4 py-3 md:px-6">
                <button class="inline-flex items-center justify-center rounded-lg bg-brand-500 px-4 py-2 text-sm font-semibold text-white shadow-theme-xs transition hover:bg-brand-600" type="submit">Salvar</button>
                <a class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-theme-xs transition hover:bg-gray-50" href="{{ route('clinics.index') }}">Voltar</a>
            </div>
        </div>
    </form>
</x-app-layout>
