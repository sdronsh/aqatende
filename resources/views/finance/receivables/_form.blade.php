@php
    $input = 'w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 shadow-theme-xs focus:border-brand-300 focus:outline-none focus:ring-3 focus:ring-brand-500/10';
    $select = $input;
    $textarea = $input;
    $valueFromCents = fn ($cents) => $cents ? number_format($cents / 100, 2, ',', '.') : '';
    $status = old('status', $receivable->status ?? 'aberto');
    $payment = old('forma_pagamento', $receivable->forma_pagamento ?? '');
@endphp

<div class="grid gap-4 md:grid-cols-12">
    <div class="md:col-span-4">
        <label class="mb-1 block text-sm font-medium text-gray-700" for="clinic_id">Clinica</label>
        <select class="{{ $select }}" id="clinic_id" name="clinic_id" required>
            <option value="">Selecione</option>
            @foreach ($clinics as $clinic)
                <option value="{{ $clinic->id }}" @selected(old('clinic_id', $receivable->clinic_id ?? '') == $clinic->id)>{{ $clinic->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="md:col-span-4">
        <label class="mb-1 block text-sm font-medium text-gray-700" for="unit_id">Unidade</label>
        <select class="{{ $select }}" id="unit_id" name="unit_id">
            <option value="">Todas as unidades</option>
            @foreach ($units as $unit)
                <option value="{{ $unit->id }}" @selected((string) old('unit_id', $receivable->unit_id ?? ($units->count() === 1 ? $units->first()->id : '')) === (string) $unit->id)>{{ $unit->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="md:col-span-4">
        <label class="mb-1 block text-sm font-medium text-gray-700" for="patient_id">Cliente</label>
        <select class="{{ $select }}" id="patient_id" name="patient_id">
            <option value="">Selecione</option>
            @foreach ($patients as $patient)
                <option value="{{ $patient->id }}" @selected(old('patient_id', $receivable->patient_id ?? '') == $patient->id)>{{ $patient->full_name }}</option>
            @endforeach
        </select>
    </div>
    <div class="md:col-span-4">
        <label class="mb-1 block text-sm font-medium text-gray-700" for="professional_id">Profissional</label>
        <select class="{{ $select }}" id="professional_id" name="professional_id">
            <option value="">Selecione</option>
            @foreach ($professionals as $professional)
                <option value="{{ $professional->id }}" @selected(old('professional_id', $receivable->professional_id ?? '') == $professional->id)>{{ $professional->display_name }}</option>
            @endforeach
        </select>
    </div>
    <div class="md:col-span-6">
        <label class="mb-1 block text-sm font-medium text-gray-700" for="appointment_id">Atendimento (opcional)</label>
        <select class="{{ $select }}" id="appointment_id" name="appointment_id">
            <option value="">Selecione</option>
            @foreach ($appointments as $appointment)
                <option value="{{ $appointment->id }}" @selected(old('appointment_id', $receivable->appointment_id ?? '') == $appointment->id)>
                    #{{ $appointment->id }} - {{ $appointment->patient?->full_name ?? 'Cliente' }} - {{ $appointment->scheduled_at?->format('d/m/Y H:i') }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="md:col-span-6">
        <label class="mb-1 block text-sm font-medium text-gray-700" for="descricao">Descricao</label>
        <input class="{{ $input }}" id="descricao" name="descricao" value="{{ old('descricao', $receivable->descricao ?? '') }}" required />
    </div>
    <div class="md:col-span-3">
        <label class="mb-1 block text-sm font-medium text-gray-700" for="valor_total">Valor total (R$)</label>
        <input class="{{ $input }}" id="valor_total" name="valor_total" inputmode="decimal" value="{{ old('valor_total', $valueFromCents($receivable->valor_total_cents ?? null)) }}" placeholder="Ex: 800,00" required />
    </div>
    <div class="md:col-span-3">
        <label class="mb-1 block text-sm font-medium text-gray-700" for="numero_parcelas">Numero de parcelas</label>
        <input class="{{ $input }}" type="number" min="1" id="numero_parcelas" name="numero_parcelas" value="{{ old('numero_parcelas', $receivable->numero_parcelas ?? 1) }}" />
    </div>
    <div class="md:col-span-3">
        <label class="mb-1 block text-sm font-medium text-gray-700" for="numero_parcela">Parcela</label>
        <input class="{{ $input }}" type="number" min="1" id="numero_parcela" name="numero_parcela" value="{{ old('numero_parcela', $receivable->numero_parcela ?? 1) }}" />
    </div>
    <div class="md:col-span-3">
        <label class="mb-1 block text-sm font-medium text-gray-700" for="valor_parcela">Valor parcela (R$)</label>
        <input class="{{ $input }}" id="valor_parcela" name="valor_parcela" inputmode="decimal" value="{{ old('valor_parcela', $valueFromCents($receivable->valor_parcela_cents ?? null)) }}" placeholder="Ex: 200,00" />
    </div>
    <div class="md:col-span-3">
        <label class="mb-1 block text-sm font-medium text-gray-700" for="data_emissao">Data emissao</label>
        <input class="{{ $input }}" type="date" id="data_emissao" name="data_emissao" value="{{ old('data_emissao', optional($receivable->data_emissao ?? null)->format('Y-m-d')) }}" />
    </div>
    <div class="md:col-span-3">
        <label class="mb-1 block text-sm font-medium text-gray-700" for="data_vencimento">Data vencimento</label>
        <input class="{{ $input }}" type="date" id="data_vencimento" name="data_vencimento" value="{{ old('data_vencimento', optional($receivable->data_vencimento ?? null)->format('Y-m-d')) }}" required />
    </div>
    <div class="md:col-span-3">
        <label class="mb-1 block text-sm font-medium text-gray-700" for="status">Status</label>
        <select class="{{ $select }}" id="status" name="status">
            <option value="aberto" @selected($status === 'aberto')>Aberto</option>
            <option value="pago" @selected($status === 'pago')>Pago</option>
            <option value="atrasado" @selected($status === 'atrasado')>Atrasado</option>
            <option value="cancelado" @selected($status === 'cancelado')>Cancelado</option>
        </select>
    </div>
    <div class="md:col-span-3">
        <label class="mb-1 block text-sm font-medium text-gray-700" for="forma_pagamento">Forma pagamento</label>
        <select class="{{ $select }}" id="forma_pagamento" name="forma_pagamento">
            <option value="">Selecione</option>
            <option value="pix" @selected($payment === 'pix')>Pix</option>
            <option value="cartao" @selected($payment === 'cartao')>Cartao</option>
            <option value="dinheiro" @selected($payment === 'dinheiro')>Dinheiro</option>
            <option value="convenio" @selected($payment === 'convenio')>Convenio</option>
        </select>
    </div>
    <div class="md:col-span-6">
        <label class="mb-1 block text-sm font-medium text-gray-700" for="categoria_financeira_id">Categoria financeira</label>
        <select class="{{ $select }}" id="categoria_financeira_id" name="categoria_financeira_id">
            <option value="">Selecione</option>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}" @selected(old('categoria_financeira_id', $receivable->categoria_financeira_id ?? '') == $category->id)>
                    {{ $category->name }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="md:col-span-12">
        <label class="mb-1 block text-sm font-medium text-gray-700" for="observacoes">Observacoes</label>
        <textarea class="{{ $textarea }}" id="observacoes" name="observacoes" rows="3">{{ old('observacoes', $receivable->observacoes ?? '') }}</textarea>
    </div>
</div>
