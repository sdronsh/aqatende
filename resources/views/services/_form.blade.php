@php
    $input = 'w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 shadow-theme-xs focus:border-brand-300 focus:outline-none focus:ring-3 focus:ring-brand-500/10';
@endphp

<div class="grid gap-4 md:grid-cols-12">
    <div class="md:col-span-6">
        <label class="mb-1 block text-sm font-medium text-gray-700" for="clinic_id">Clinica</label>
        @php $clinicId = old('clinic_id', $service->clinic_id ?? null); @endphp
        <select class="{{ $input }}" id="clinic_id" name="clinic_id" required>
            <option value="">Selecione</option>
            @foreach ($clinics as $clinic)
                <option value="{{ $clinic->id }}" @selected((string) $clinicId === (string) $clinic->id)>{{ $clinic->name }}</option>
            @endforeach
        </select>
        <x-input-error class="mt-1" :messages="$errors->get('clinic_id')" />
    </div>
    <div class="md:col-span-6">
        <label class="mb-1 block text-sm font-medium text-gray-700" for="unit_id">Unidade</label>
        @php $unitId = old('unit_id', $service->unit_id ?? null); @endphp
        <select class="{{ $input }}" id="unit_id" name="unit_id">
            <option value="">Todas / nao informado</option>
            @foreach ($units as $unit)
                <option value="{{ $unit->id }}" @selected((string) $unitId === (string) $unit->id)>{{ $unit->name }}</option>
            @endforeach
        </select>
        <x-input-error class="mt-1" :messages="$errors->get('unit_id')" />
    </div>
    <div class="md:col-span-8">
        <label class="mb-1 block text-sm font-medium text-gray-700" for="name">Nome</label>
        <input class="{{ $input }}" id="name" name="name" value="{{ old('name', $service->name ?? '') }}" required />
        <x-input-error class="mt-1" :messages="$errors->get('name')" />
    </div>
    <div class="md:col-span-2">
        <label class="mb-1 block text-sm font-medium text-gray-700" for="duration_minutes">Duracao (min)</label>
        <input class="{{ $input }}" id="duration_minutes" type="number" name="duration_minutes" value="{{ old('duration_minutes', $service->duration_minutes ?? 30) }}" required />
        <x-input-error class="mt-1" :messages="$errors->get('duration_minutes')" />
    </div>
    <div class="md:col-span-2">
        <label class="mb-1 block text-sm font-medium text-gray-700" for="modality">Modalidade</label>
        @php
            $modality = old('modality', $service->modality ?? 'presencial');
            if (in_array($modality, ['teleconsulta', 'whatsapp'], true)) {
                $modality = 'home_care';
            }
        @endphp
        <select class="{{ $input }}" id="modality" name="modality" required>
            <option value="presencial" @selected($modality === 'presencial')>Presencial</option>
            <option value="home_care" @selected($modality === 'home_care')>Home Care</option>
        </select>
        <x-input-error class="mt-1" :messages="$errors->get('modality')" />
    </div>
    <div class="md:col-span-4">
        <label class="mb-1 block text-sm font-medium text-gray-700" for="price">Preco (R$)</label>
        @php
            $priceValue = old('price');
            if ($priceValue === null && isset($service->price_cents)) {
                $priceValue = number_format($service->price_cents / 100, 2, ',', '.');
            }
        @endphp
        <input class="{{ $input }}" id="price" type="text" inputmode="decimal" name="price" value="{{ $priceValue }}" placeholder="Ex: 80,00" required />
        <x-input-error class="mt-1" :messages="$errors->get('price')" />
    </div>
    <div class="md:col-span-4">
        @php $active = old('active', $service->active ?? true); @endphp
        <label class="inline-flex items-center gap-2 text-sm text-gray-600 mt-6">
            <input type="checkbox" name="active" value="1" class="h-4 w-4 rounded border-gray-300 text-brand-500 focus:ring-brand-500" @checked($active) />
            Servico ativo
        </label>
    </div>
</div>
