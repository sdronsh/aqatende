<x-app-layout>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-gray-800">Perfil</h2>
    </x-slot>

    <div class="space-y-4">
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-theme-sm md:p-6">
            @include('profile.partials.update-profile-information-form')
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-theme-sm md:p-6">
            @include('profile.partials.update-password-form')
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-theme-sm md:p-6">
            @include('profile.partials.delete-user-form')
        </div>
    </div>
</x-app-layout>
