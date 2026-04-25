@php
    $input = 'w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 shadow-theme-xs focus:border-brand-300 focus:outline-none focus:ring-3 focus:ring-brand-500/10';
    $select = $input;
    $textarea = $input;
    $valueFromCents = fn ($cents) => $cents ? number_format($cents / 100, 2, ',', '.') : '';
    $status = old('status', $payable->status ?? 'aberto');
    $payment = old('forma_pagamento', $payable->forma_pagamento ?? '');
@endphp

<div class="grid gap-4 md:grid-cols-12">
    <div class="md:col-span-4">
        <label class="mb-1 block text-sm font-medium text-gray-700" for="clinic_id">Clinica</label>
        <select class="{{ $select }}" id="clinic_id" name="clinic_id" required>
            <option value="">Selecione</option>
            @foreach ($clinics as $clinic)
                <option value="{{ $clinic->id }}" @selected(old('clinic_id', $payable->clinic_id ?? '') == $clinic->id)>{{ $clinic->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="md:col-span-4">
        <label class="mb-1 block text-sm font-medium text-gray-700" for="unit_id">Unidade</label>
        <select class="{{ $select }}" id="unit_id" name="unit_id">
            <option value="">Todas as unidades</option>
            @foreach ($units as $unit)
                <option value="{{ $unit->id }}" @selected(old('unit_id', $payable->unit_id ?? '') == $unit->id)>{{ $unit->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="md:col-span-4">
        <label class="mb-1 block text-sm font-medium text-gray-700" for="fornecedor">Fornecedor</label>
        <input class="{{ $input }}" id="fornecedor" name="fornecedor" value="{{ old('fornecedor', $payable->fornecedor ?? '') }}" />
    </div>
    <div class="md:col-span-6">
        <label class="mb-1 block text-sm font-medium text-gray-700" for="descricao">Descricao</label>
        <input class="{{ $input }}" id="descricao" name="descricao" value="{{ old('descricao', $payable->descricao ?? '') }}" required />
    </div>
    <div class="md:col-span-3">
        <label class="mb-1 block text-sm font-medium text-gray-700" for="valor">Valor (R$)</label>
        <input class="{{ $input }}" id="valor" name="valor" inputmode="decimal" value="{{ old('valor', $valueFromCents($payable->valor_cents ?? null)) }}" placeholder="Ex: 800,00" required />
    </div>
    <div class="md:col-span-3">
        <label class="mb-1 block text-sm font-medium text-gray-700" for="centro_custo">Centro de custo</label>
        <input class="{{ $input }}" id="centro_custo" name="centro_custo" value="{{ old('centro_custo', $payable->centro_custo ?? '') }}" />
    </div>
    <div class="md:col-span-3">
        <label class="mb-1 block text-sm font-medium text-gray-700" for="data_emissao">Data emissao</label>
        <input class="{{ $input }}" type="date" id="data_emissao" name="data_emissao" value="{{ old('data_emissao', optional($payable->data_emissao ?? null)->format('Y-m-d')) }}" />
    </div>
    <div class="md:col-span-3">
        <label class="mb-1 block text-sm font-medium text-gray-700" for="data_vencimento">Data vencimento</label>
        <input class="{{ $input }}" type="date" id="data_vencimento" name="data_vencimento" value="{{ old('data_vencimento', optional($payable->data_vencimento ?? null)->format('Y-m-d')) }}" required />
    </div>
    <div class="md:col-span-3">
        <label class="mb-1 block text-sm font-medium text-gray-700" for="status">Status</label>
        <select class="{{ $select }}" id="status" name="status">
            <option value="aberto" @selected($status === 'aberto')>Aberto</option>
            <option value="pago" @selected($status === 'pago')>Pago</option>
            <option value="atrasado" @selected($status === 'atrasado')>Atrasado</option>
        </select>
    </div>
    <div class="md:col-span-3">
        <label class="mb-1 block text-sm font-medium text-gray-700" for="forma_pagamento">Forma pagamento</label>
        <select class="{{ $select }}" id="forma_pagamento" name="forma_pagamento">
            <option value="">Selecione</option>
            <option value="pix" @selected($payment === 'pix')>Pix</option>
            <option value="cartao" @selected($payment === 'cartao')>Cartao</option>
            <option value="dinheiro" @selected($payment === 'dinheiro')>Dinheiro</option>
            <option value="boleto" @selected($payment === 'boleto')>Boleto</option>
        </select>
    </div>
    <div class="md:col-span-6">
        <label class="mb-1 block text-sm font-medium text-gray-700" for="categoria_financeira_id">Categoria financeira</label>
        <select class="{{ $select }}" id="categoria_financeira_id" name="categoria_financeira_id">
            <option value="">Selecione</option>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}" @selected(old('categoria_financeira_id', $payable->categoria_financeira_id ?? '') == $category->id)>
                    {{ $category->name }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="md:col-span-12">
        <label class="mb-1 block text-sm font-medium text-gray-700" for="observacoes">Observacoes</label>
        <textarea class="{{ $textarea }}" id="observacoes" name="observacoes" rows="3">{{ old('observacoes', $payable->observacoes ?? '') }}</textarea>
    </div>
</div>
