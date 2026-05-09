@php
    $input = 'w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 shadow-theme-xs focus:border-brand-300 focus:outline-none focus:ring-3 focus:ring-brand-500/10';
    $select = $input;
    $active = old('active', $account->active ?? true);
    $valueFromCents = fn ($cents) => $cents ? number_format($cents / 100, 2, ',', '.') : '';
    $type = old('type', $account->type ?? 'caixa');
@endphp

<div class="grid gap-4 md:grid-cols-12">
    <div class="md:col-span-4">
        <label class="mb-1 block text-sm font-medium text-gray-700" for="clinic_id">Clinica</label>
        <select class="{{ $select }}" id="clinic_id" name="clinic_id" required>
            <option value="">Selecione</option>
            @foreach ($clinics as $clinic)
                <option value="{{ $clinic->id }}" @selected(old('clinic_id', $account->clinic_id ?? '') == $clinic->id)>{{ $clinic->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="md:col-span-4">
        <label class="mb-1 block text-sm font-medium text-gray-700" for="unit_id">Unidade</label>
        <select class="{{ $select }}" id="unit_id" name="unit_id" required>
            <option value="">Selecione</option>
            @foreach ($units as $unit)
                <option value="{{ $unit->id }}" @selected((string) old('unit_id', $account->unit_id ?? ($units->count() === 1 ? $units->first()->id : '')) === (string) $unit->id)>{{ $unit->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="md:col-span-4">
        <label class="mb-1 block text-sm font-medium text-gray-700" for="name">Nome</label>
        <input class="{{ $input }}" id="name" name="name" value="{{ old('name', $account->name ?? '') }}" required />
    </div>
    <div class="md:col-span-4">
        <label class="mb-1 block text-sm font-medium text-gray-700" for="type">Tipo</label>
        <select class="{{ $select }}" id="type" name="type" required>
            <option value="caixa" @selected($type === 'caixa')>Caixa</option>
            <option value="banco" @selected($type === 'banco')>Banco</option>
            <option value="pix" @selected($type === 'pix')>Pix</option>
        </select>
    </div>
    <div class="md:col-span-4">
        <label class="mb-1 block text-sm font-medium text-gray-700" for="bank_name">Banco</label>
        <input class="{{ $input }}" id="bank_name" name="bank_name" value="{{ old('bank_name', $account->bank_name ?? '') }}" />
    </div>
    <div class="md:col-span-2">
        <label class="mb-1 block text-sm font-medium text-gray-700" for="agency">Agencia</label>
        <input class="{{ $input }}" id="agency" name="agency" value="{{ old('agency', $account->agency ?? '') }}" />
    </div>
    <div class="md:col-span-2">
        <label class="mb-1 block text-sm font-medium text-gray-700" for="account_number">Conta</label>
        <input class="{{ $input }}" id="account_number" name="account_number" value="{{ old('account_number', $account->account_number ?? '') }}" />
    </div>
    <div class="md:col-span-4">
        <label class="mb-1 block text-sm font-medium text-gray-700" for="pix_key">Chave Pix</label>
        <input class="{{ $input }}" id="pix_key" name="pix_key" value="{{ old('pix_key', $account->pix_key ?? '') }}" />
    </div>
    <div class="md:col-span-4">
        <label class="mb-1 block text-sm font-medium text-gray-700" for="initial_balance">Saldo inicial (R$)</label>
        <input class="{{ $input }}" id="initial_balance" name="initial_balance" inputmode="decimal" value="{{ old('initial_balance', $valueFromCents($account->initial_balance_cents ?? null)) }}" placeholder="Ex: 1.000,00" />
    </div>
    <div class="md:col-span-12">
        <label class="inline-flex items-center gap-2 text-sm text-gray-600">
            <input type="checkbox" name="active" value="1" class="h-4 w-4 rounded border-gray-300 text-brand-500 focus:ring-brand-500" @checked($active) />
            Ativa
        </label>
    </div>
</div>
