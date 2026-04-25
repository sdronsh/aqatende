<x-app-layout>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-gray-800">Novo profissional</h2>
    </x-slot>

    <form method="POST" action="{{ route('professionals.store') }}" class="rounded-xl border border-gray-200 bg-white p-4 shadow-theme-sm md:p-6">
        @csrf
        @include('professionals._form', [
            'professional' => new \App\Models\Professional(),
            'users' => $users,
            'units' => $units,
            'specialties' => $specialties,
            'schedulesByWeekday' => $schedulesByWeekday,
        ])

        <div class="mt-6 flex items-center gap-2">
            <button class="inline-flex items-center justify-center rounded-lg bg-brand-500 px-4 py-2 text-sm font-semibold text-white shadow-theme-xs transition hover:bg-brand-600" type="submit">Salvar</button>
            <a class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-theme-xs transition hover:bg-gray-50" href="{{ route('professionals.index') }}">Voltar</a>
        </div>
    </form>
</x-app-layout>
