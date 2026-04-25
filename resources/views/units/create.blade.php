<x-app-layout>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-gray-800">Nova unidade</h2>
    </x-slot>

    <form method="POST" action="{{ route('units.store') }}" class="rounded-xl border border-gray-200 bg-white p-4 shadow-theme-sm md:p-6">
        @csrf
        @include('units._form', ['unit' => new \App\Models\Unit(), 'clinics' => $clinics, 'specialties' => $specialties])

        <div class="mt-6 flex items-center gap-2">
            <button class="inline-flex items-center justify-center rounded-lg bg-brand-500 px-4 py-2 text-sm font-semibold text-white shadow-theme-xs transition hover:bg-brand-600" type="submit">Salvar</button>
            <a class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-theme-xs transition hover:bg-gray-50" href="{{ route('units.index') }}">Voltar</a>
        </div>
    </form>
</x-app-layout>
