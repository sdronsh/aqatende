@php
    $input = 'w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 shadow-theme-xs focus:border-brand-300 focus:outline-none focus:ring-3 focus:ring-brand-500/10';
@endphp

<div class="grid gap-4 md:grid-cols-12">
    <div class="md:col-span-6">
        <label class="mb-1 block text-sm font-medium text-gray-700" for="company_id">Empresa</label>
        @php
            $companyValue = old('company_id', $selectedCompanyId ?? '');
            $lockCompany = $companyLocked ?? false;
        @endphp
        <select class="{{ $input }}" id="company_id" name="company_id" @disabled($lockCompany)>
            <option value="">Selecione a empresa</option>
            @foreach ($companies as $company)
                <option value="{{ $company->id }}" @selected((string) $companyValue === (string) $company->id)>{{ $company->name }}</option>
            @endforeach
        </select>
        @if ($lockCompany)
            <input type="hidden" name="company_id" value="{{ $companyValue }}">
        @endif
        <x-input-error class="mt-1" :messages="$errors->get('company_id')" />
    </div>
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
        <input class="{{ $input }}" id="email" type="email" name="email" value="{{ old('email', $user->email ?? '') }}" required />
        <x-input-error class="mt-1" :messages="$errors->get('email')" />
    </div>
    <div class="md:col-span-6">
        <label class="mb-1 block text-sm font-medium text-gray-700" for="password">Senha</label>
        <input class="{{ $input }}" id="password" type="password" name="password" {{ isset($user) ? '' : 'required' }} />
        <x-input-error class="mt-1" :messages="$errors->get('password')" />
    </div>
    <div class="md:col-span-6">
        <label class="mb-1 block text-sm font-medium text-gray-700" for="role_id">Perfil</label>
        @php $roleId = old('role_id', $pivot->role_id ?? null); @endphp
        <select class="{{ $input }}" id="role_id" name="role_id">
            <option value="">Sem perfil</option>
            @foreach ($roles as $role)
                <option value="{{ $role->id }}" data-company="{{ $role->company_id ?? '' }}" @selected((string) $roleId === (string) $role->id)>{{ $role->name }}</option>
            @endforeach
        </select>
        <x-input-error class="mt-1" :messages="$errors->get('role_id')" />
    </div>
    @if (auth()->user()->is_platform_admin)
        <div class="md:col-span-12">
            @php $isMaster = old('is_master', $pivot->is_master ?? false); @endphp
            <label class="inline-flex items-center gap-2 text-sm text-gray-600">
                <input type="checkbox" name="is_master" value="1" class="h-4 w-4 rounded border-gray-300 text-brand-500 focus:ring-brand-500" @checked($isMaster) />
                Usuario master da empresa
            </label>
        </div>
    @endif
</div>

@if (auth()->user()->is_platform_admin)
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const companySelect = document.getElementById('company_id');
            const roleSelect = document.getElementById('role_id');
            if (!companySelect || !roleSelect) {
                return;
            }

            const options = Array.from(roleSelect.options);
            const filterRoles = () => {
                const companyId = companySelect.value;
                options.forEach((option) => {
                    if (!option.value) {
                        option.hidden = false;
                        return;
                    }
                    option.hidden = option.dataset.company !== companyId;
                    if (option.hidden && option.selected) {
                        option.selected = false;
                    }
                });
            };

            companySelect.addEventListener('change', filterRoles);
            filterRoles();
        });
    </script>
@endif
