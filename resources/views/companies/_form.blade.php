@php
    $input = 'w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 shadow-theme-xs focus:border-brand-300 focus:outline-none focus:ring-3 focus:ring-brand-500/10';
    $cnpjValue = old('cnpj', $company->cnpj ?? '');
    $cnpjDigits = preg_replace('/\D/', '', (string) $cnpjValue);
    if (strlen($cnpjDigits) === 11) {
        $cnpjValue = substr($cnpjDigits, 0, 3).'.'.substr($cnpjDigits, 3, 3).'.'.substr($cnpjDigits, 6, 3).'-'.substr($cnpjDigits, 9, 2);
    } elseif (strlen($cnpjDigits) === 14) {
        $cnpjValue = substr($cnpjDigits, 0, 2).'.'.substr($cnpjDigits, 2, 3).'.'.substr($cnpjDigits, 5, 3).'/'.substr($cnpjDigits, 8, 4).'-'.substr($cnpjDigits, 12, 2);
    }
@endphp

<div class="grid gap-4 md:grid-cols-12">
    <div class="md:col-span-3">
        <label class="mb-1 block text-sm font-medium text-gray-700" for="code">Codigo interno</label>
        <input class="{{ $input }}" id="code" value="{{ $company->code ?? 'Gerado automaticamente' }}" disabled />
    </div>
    <div class="md:col-span-5">
        <label class="mb-1 block text-sm font-medium text-gray-700" for="name">Nome</label>
        <input class="{{ $input }}" id="name" name="name" value="{{ old('name', $company->name ?? '') }}" required />
        <x-input-error class="mt-1" :messages="$errors->get('name')" />
    </div>
    <div class="md:col-span-4">
        <label class="mb-1 block text-sm font-medium text-gray-700" for="legal_name">Razao social</label>
        <input class="{{ $input }}" id="legal_name" name="legal_name" value="{{ old('legal_name', $company->legal_name ?? '') }}" />
        <x-input-error class="mt-1" :messages="$errors->get('legal_name')" />
    </div>
    <div class="md:col-span-4">
        <label class="mb-1 block text-sm font-medium text-gray-700" for="cnpj">CNPJ ou CPF</label>
        <input class="{{ $input }}" id="cnpj" name="cnpj" value="{{ $cnpjValue }}" data-mask="cnpj" required />
        <x-input-error class="mt-1" :messages="$errors->get('cnpj')" />
    </div>
    <div class="md:col-span-4">
        <label class="mb-1 block text-sm font-medium text-gray-700" for="license_code">Codigo da licenca</label>
        <input class="{{ $input }}" id="license_code" name="license_code" value="{{ old('license_code', $company->license_code ?? '') }}" />
        <x-input-error class="mt-1" :messages="$errors->get('license_code')" />
    </div>
    @if (auth()->user()?->is_platform_admin)
        <div class="md:col-span-4">
            <label class="mb-1 block text-sm font-medium text-gray-700" for="business_activity">Ramo de atividade</label>
            @php $businessActivity = old('business_activity', $company->business_activity ?? \App\Models\Company::defaultBusinessActivity()); @endphp
            <select class="{{ $input }}" id="business_activity" name="business_activity" required>
                @foreach (\App\Models\Company::businessActivityOptions() as $value => $label)
                    <option value="{{ $value }}" @selected($businessActivity === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <x-input-error class="mt-1" :messages="$errors->get('business_activity')" />
        </div>
    @endif
    <div class="md:col-span-4">
        <label class="mb-1 block text-sm font-medium text-gray-700" for="email">Email</label>
        <input class="{{ $input }}" id="email" name="email" value="{{ old('email', $company->email ?? '') }}" />
        <x-input-error class="mt-1" :messages="$errors->get('email')" />
    </div>
    <div class="md:col-span-4">
        <label class="mb-1 block text-sm font-medium text-gray-700" for="phone">Telefone</label>
        <input class="{{ $input }}" id="phone" name="phone" value="{{ old('phone', $company->phone ?? '') }}" />
        <x-input-error class="mt-1" :messages="$errors->get('phone')" />
    </div>
    <div class="md:col-span-4">
        @php $active = old('active', $company->active ?? true); @endphp
        <label class="inline-flex items-center gap-2 text-sm text-gray-600 mt-6">
            <input type="checkbox" name="active" value="1" class="h-4 w-4 rounded border-gray-300 text-brand-500 focus:ring-brand-500" @checked($active) />
            Empresa ativa
        </label>
    </div>
</div>
