<x-app-layout>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-gray-800">Editar cliente</h2>
    </x-slot>

    <form method="POST" action="{{ route('patients.update', $patient) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        <div class="rounded-xl border border-gray-200 bg-white shadow-theme-sm">
            <div class="p-4 md:p-6">
                @include('patients._form', ['patient' => $patient])
            </div>
            <div class="sticky bottom-0 z-20 grid gap-2 border-t border-gray-100 bg-gray-50 px-4 py-3 shadow-[0_-8px_20px_-18px_rgba(16,24,40,0.45)] sm:flex sm:flex-wrap sm:items-center md:px-6">
                <button class="inline-flex w-full items-center justify-center rounded-lg bg-brand-500 px-4 py-2 text-sm font-semibold text-white shadow-theme-xs transition hover:bg-brand-600 sm:w-auto" type="submit">Salvar</button>
                <a class="inline-flex w-full items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-theme-xs transition hover:bg-gray-50 sm:w-auto" href="{{ route('patients.index') }}">Voltar</a>
            </div>
        </div>
    </form>
</x-app-layout>
