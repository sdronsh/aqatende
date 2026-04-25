@php
    $input = 'w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 shadow-theme-xs focus:border-brand-300 focus:outline-none focus:ring-3 focus:ring-brand-500/10';
@endphp

<div class="grid gap-4 md:grid-cols-12">
    <div class="md:col-span-6">
        <label class="mb-1 block text-sm font-medium text-gray-700" for="name">Nome</label>
        <input class="{{ $input }}" id="name" name="name" value="{{ old('name', $user->name ?? '') }}" required />
        <x-input-error class="mt-1" :messages="$errors->get('name')" />
    </div>
    <div class="md:col-span-6">
        <label class="mb-1 block text-sm font-medium text-gray-700" for="username">Username</label>
        <input class="{{ $input }}" id="username" name="username" value="{{ old('username', $user->username ?? '') }}" required />
        <x-input-error class="mt-1" :messages="$errors->get('username')" />
    </div>
    <div class="md:col-span-6">
        <label class="mb-1 block text-sm font-medium text-gray-700" for="email">Email</label>
        <input class="{{ $input }}" id="email" name="email" type="email" value="{{ old('email', $user->email ?? '') }}" required />
        <x-input-error class="mt-1" :messages="$errors->get('email')" />
    </div>
    <div class="md:col-span-6">
        <label class="mb-1 block text-sm font-medium text-gray-700" for="password">Senha</label>
        <input class="{{ $input }}" id="password" name="password" type="password" {{ isset($user) ? '' : 'required' }} />
        <x-input-error class="mt-1" :messages="$errors->get('password')" />
    </div>
</div>
