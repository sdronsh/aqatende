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
        @php $unitId = old('unit_id', $service->unit_id ?? ($units->count() === 1 ? $units->first()->id : null)); @endphp
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
    <div class="md:col-span-4">
        @php $sharedService = old('shared_service', $service->shared_service ?? false); @endphp
        <label class="inline-flex items-start gap-2 text-sm text-gray-600 mt-6">
            <input type="checkbox" name="shared_service" value="1" class="mt-0.5 h-4 w-4 rounded border-gray-300 text-brand-500 focus:ring-brand-500" @checked($sharedService) />
            <span>
                <span class="font-medium text-gray-700">Servico compartilhado</span>
                <span class="block text-xs text-gray-500">Permite iniciar mais de um atendimento simultaneo para o mesmo profissional.</span>
            </span>
        </label>
    </div>
    <div class="md:col-span-4">
        @php $isPackage = old('is_package', $service->is_package ?? false); @endphp
        <label class="inline-flex items-start gap-2 text-sm text-gray-600 mt-6">
            <input type="checkbox" name="is_package" value="1" class="mt-0.5 h-4 w-4 rounded border-gray-300 text-brand-500 focus:ring-brand-500" @checked($isPackage) />
            <span>
                <span class="font-medium text-gray-700">Servico composto / pacote</span>
                <span class="block text-xs text-gray-500">Usa preco proprio e agenda os servicos internos.</span>
            </span>
        </label>
    </div>
    <div class="md:col-span-12">
        <label class="mb-1 block text-sm font-medium text-gray-700">Servicos internos do pacote</label>
        @php
            $selectedPackageServiceIds = collect(old('package_service_ids', $service->exists ? $service->packageItems->pluck('id')->all() : []))
                ->map(fn ($id) => (int) $id);
        @endphp
        <div class="grid gap-2 rounded-lg border border-gray-200 bg-white p-3 md:grid-cols-2">
            @forelse (($componentServices ?? collect()) as $componentService)
                <label class="flex items-center gap-2 rounded-lg border border-gray-100 px-3 py-2 text-sm text-gray-700">
                    <input
                        type="checkbox"
                        name="package_service_ids[]"
                        value="{{ $componentService->id }}"
                        class="h-4 w-4 rounded border-gray-300 text-brand-500 focus:ring-brand-500"
                        @checked($selectedPackageServiceIds->contains((int) $componentService->id))
                    />
                    <span class="min-w-0">
                        <span class="block truncate font-medium">{{ $componentService->name }}</span>
                        <span class="block text-xs text-gray-500">{{ $componentService->duration_minutes }} min · R$ {{ number_format(($componentService->price_cents ?? 0) / 100, 2, ',', '.') }}</span>
                    </span>
                </label>
            @empty
                <div class="text-sm text-gray-500">Cadastre servicos simples para montar um pacote.</div>
            @endforelse
        </div>
        <x-input-error class="mt-1" :messages="$errors->get('package_service_ids')" />
    </div>
</div>
