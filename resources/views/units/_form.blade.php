@php
    $input = 'w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 shadow-theme-xs focus:border-brand-300 focus:outline-none focus:ring-3 focus:ring-brand-500/10';
@endphp

<div class="grid gap-4 md:grid-cols-12">
    <div class="md:col-span-6">
        <label class="mb-1 block text-sm font-medium text-gray-700" for="clinic_id">Clinica</label>
        @php $clinicId = old('clinic_id', $unit->clinic_id ?? null); @endphp
        <select class="{{ $input }}" id="clinic_id" name="clinic_id" required>
            <option value="">Selecione</option>
            @foreach ($clinics as $clinic)
                <option value="{{ $clinic->id }}" @selected((string) $clinicId === (string) $clinic->id)>{{ $clinic->name }}</option>
            @endforeach
        </select>
        <x-input-error class="mt-1" :messages="$errors->get('clinic_id')" />
    </div>
    <div class="md:col-span-6">
        <label class="mb-1 block text-sm font-medium text-gray-700" for="name">Nome</label>
        <input class="{{ $input }}" id="name" name="name" value="{{ old('name', $unit->name ?? '') }}" required />
        <x-input-error class="mt-1" :messages="$errors->get('name')" />
    </div>
    <div class="md:col-span-8">
        <label class="mb-1 block text-sm font-medium text-gray-700" for="address_line1">Endereco</label>
        <input class="{{ $input }}" id="address_line1" name="address_line1" value="{{ old('address_line1', $unit->address_line1 ?? '') }}" required />
        <x-input-error class="mt-1" :messages="$errors->get('address_line1')" />
    </div>
    <div class="md:col-span-4">
        <label class="mb-1 block text-sm font-medium text-gray-700" for="address_line2">Complemento</label>
        <input class="{{ $input }}" id="address_line2" name="address_line2" value="{{ old('address_line2', $unit->address_line2 ?? '') }}" />
        <x-input-error class="mt-1" :messages="$errors->get('address_line2')" />
    </div>
    <div class="md:col-span-4">
        <label class="mb-1 block text-sm font-medium text-gray-700" for="city">Cidade</label>
        <input class="{{ $input }}" id="city" name="city" value="{{ old('city', $unit->city ?? '') }}" required />
        <x-input-error class="mt-1" :messages="$errors->get('city')" />
    </div>
    <div class="md:col-span-2">
        <label class="mb-1 block text-sm font-medium text-gray-700" for="state">UF</label>
        <input class="{{ $input }}" id="state" name="state" value="{{ old('state', $unit->state ?? '') }}" required />
        <x-input-error class="mt-1" :messages="$errors->get('state')" />
    </div>
    <div class="md:col-span-2">
        <label class="mb-1 block text-sm font-medium text-gray-700" for="zip">CEP</label>
        <input class="{{ $input }}" id="zip" name="zip" value="{{ old('zip', $unit->zip ?? '') }}" required />
        <x-input-error class="mt-1" :messages="$errors->get('zip')" />
    </div>
    <div class="md:col-span-4">
        <label class="mb-1 block text-sm font-medium text-gray-700" for="phone">Telefone</label>
        <input class="{{ $input }}" id="phone" name="phone" value="{{ old('phone', $unit->phone ?? '') }}" />
        <x-input-error class="mt-1" :messages="$errors->get('phone')" />
    </div>
    <div class="md:col-span-8">
        <label class="mb-2 block text-sm font-medium text-gray-700">Especialidades atendidas</label>
        @php
            $selected = old('specialties', $unit->specialties?->pluck('id')->all() ?? []);
        @endphp
        <div class="grid gap-2 rounded-lg border border-gray-200 bg-white p-3 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($specialties as $specialty)
                <label class="flex items-center gap-2 text-sm text-gray-600">
                    <input type="checkbox" name="specialties[]" value="{{ $specialty->id }}" class="h-4 w-4 rounded border-gray-300 text-brand-500 focus:ring-brand-500" @checked(in_array($specialty->id, $selected, true)) />
                    {{ $specialty->name }}
                </label>
            @endforeach
        </div>
        <x-input-error class="mt-1" :messages="$errors->get('specialties')" />
    </div>
    <div class="md:col-span-4">
        @php $active = old('active', $unit->active ?? true); @endphp
        <label class="inline-flex items-center gap-2 text-sm text-gray-600 mt-6">
            <input type="checkbox" name="active" value="1" class="h-4 w-4 rounded border-gray-300 text-brand-500 focus:ring-brand-500" @checked($active) />
            Unidade ativa
        </label>
    </div>
</div>
