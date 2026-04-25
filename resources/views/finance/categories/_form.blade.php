@php
    $input = 'w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 shadow-theme-xs focus:border-brand-300 focus:outline-none focus:ring-3 focus:ring-brand-500/10';
    $select = $input;
    $active = old('active', $category->active ?? true);
@endphp

<div class="grid gap-4 md:grid-cols-12">
    <div class="md:col-span-4">
        <label class="mb-1 block text-sm font-medium text-gray-700" for="clinic_id">Clinica</label>
        <select class="{{ $select }}" id="clinic_id" name="clinic_id" required>
            <option value="">Selecione</option>
            @foreach ($clinics as $clinic)
                <option value="{{ $clinic->id }}" @selected(old('clinic_id', $category->clinic_id ?? '') == $clinic->id)>{{ $clinic->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="md:col-span-4">
        <label class="mb-1 block text-sm font-medium text-gray-700" for="name">Nome</label>
        <input class="{{ $input }}" id="name" name="name" value="{{ old('name', $category->name ?? '') }}" required />
    </div>
    <div class="md:col-span-4">
        <label class="mb-1 block text-sm font-medium text-gray-700" for="type">Tipo</label>
        @php $type = old('type', $category->type ?? 'ambos'); @endphp
        <select class="{{ $select }}" id="type" name="type" required>
            <option value="ambos" @selected($type === 'ambos')>Ambos</option>
            <option value="receber" @selected($type === 'receber')>Receber</option>
            <option value="pagar" @selected($type === 'pagar')>Pagar</option>
        </select>
    </div>
    <div class="md:col-span-12">
        <label class="inline-flex items-center gap-2 text-sm text-gray-600">
            <input type="checkbox" name="active" value="1" class="h-4 w-4 rounded border-gray-300 text-brand-500 focus:ring-brand-500" @checked($active) />
            Ativa
        </label>
    </div>
</div>
