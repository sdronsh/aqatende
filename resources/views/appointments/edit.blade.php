<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <div class="text-xs uppercase text-gray-400">Agendamento</div>
                <h2 class="text-lg font-semibold text-gray-800">Editar agendamento</h2>
            </div>
            <a class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-700 shadow-theme-xs hover:bg-gray-50" href="{{ route('appointments.index') }}">Voltar</a>
        </div>
    </x-slot>

    <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-theme-sm">

        <form method="POST" action="{{ route('appointments.update', $appointment) }}" class="space-y-6">
            @csrf
            @method('PUT')
            @include('appointments._form')

            <div class="flex items-center justify-end gap-2 border-t border-gray-100 pt-4">
                <a class="rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-theme-xs hover:bg-gray-50" href="{{ route('appointments.index') }}">Cancelar</a>
                <button class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-semibold text-white shadow-theme-xs hover:bg-brand-600" type="submit">Salvar</button>
            </div>
        </form>
    </div>
</x-app-layout>
