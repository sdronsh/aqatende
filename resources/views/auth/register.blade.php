<x-guest-layout>
    <form method="POST" action="{{ route('register') }}" class="space-y-4">
        @csrf

        <div>
            <label class="mb-1 block text-sm font-medium text-gray-700" for="name">Nome</label>
            <input id="name" class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 shadow-theme-xs focus:border-brand-300 focus:outline-none focus:ring-3 focus:ring-brand-500/10" type="text" name="name" value="{{ old('name') }}" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-1" />
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-gray-700" for="email">Email</label>
            <input id="email" class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 shadow-theme-xs focus:border-brand-300 focus:outline-none focus:ring-3 focus:ring-brand-500/10" type="email" name="email" value="{{ old('email') }}" required autocomplete="email" />
            <x-input-error :messages="$errors->get('email')" class="mt-1" />
        </div>

        <div>
        <label class="mb-1 block text-sm font-medium text-gray-700" for="company_code">CNPJ ou CPF</label>
        <input id="company_code" class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 shadow-theme-xs focus:border-brand-300 focus:outline-none focus:ring-3 focus:ring-brand-500/10" type="text" name="company_code" value="{{ old('company_code') }}" data-mask="cnpj" required autocomplete="organization" />
            <x-input-error :messages="$errors->get('company_code')" class="mt-1" />
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-gray-700" for="username">Username</label>
            <input id="username" class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 shadow-theme-xs focus:border-brand-300 focus:outline-none focus:ring-3 focus:ring-brand-500/10" type="text" name="username" value="{{ old('username') }}" required autocomplete="username" />
            <x-input-error :messages="$errors->get('username')" class="mt-1" />
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-gray-700" for="password">Senha</label>
            <input id="password" class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 shadow-theme-xs focus:border-brand-300 focus:outline-none focus:ring-3 focus:ring-brand-500/10" type="password" name="password" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-1" />
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-gray-700" for="password_confirmation">Confirmar senha</label>
            <input id="password_confirmation" class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 shadow-theme-xs focus:border-brand-300 focus:outline-none focus:ring-3 focus:ring-brand-500/10" type="password" name="password_confirmation" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-1" />
        </div>

        <div class="flex items-center justify-between">
            <a class="text-sm text-gray-500 underline" href="{{ route('login') }}">Ja tem conta?</a>
            <x-primary-button>
                Cadastrar
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
