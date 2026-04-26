@php
    $input = 'w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 shadow-theme-xs focus:border-brand-300 focus:outline-none focus:ring-3 focus:ring-brand-500/10';
    $scheduledValue = old('scheduled_at', optional($appointment->scheduled_at)->format('Y-m-d\TH:i'));
    $statusValue = old('status', $appointment->status ?? 'agendado');
    $channelValue = old('channel', $appointment->channel ?? 'presencial');
    if ($channelValue === 'whatsapp') {
        $channelValue = 'home_care';
    }
    $paymentStatus = old('payment_status', $appointment->payment_status ?? 'pending');
    $paymentMethod = old('forma_pagamento', $appointment->receivable->forma_pagamento ?? '');
    $isCreate = ! $appointment->exists;
    $selectedWeekdays = collect(old('recurrence_weekdays', []))->map(fn ($weekday) => (string) $weekday)->all();
@endphp

<div class="grid gap-4 md:grid-cols-12">
    <div class="md:col-span-6">
        <label class="mb-1 block text-sm font-medium text-gray-700" for="unit_id">Unidade</label>
        @php $unitId = old('unit_id', $appointment->unit_id ?? null); @endphp
        <select class="{{ $input }}" id="unit_id" name="unit_id" required>
            <option value="">Selecione</option>
            @foreach ($units as $unit)
                <option value="{{ $unit->id }}" @selected((string) $unitId === (string) $unit->id)>{{ $unit->name }}</option>
            @endforeach
        </select>
        <x-input-error class="mt-1" :messages="$errors->get('unit_id')" />
    </div>
    <div class="md:col-span-6">
        <label class="mb-1 block text-sm font-medium text-gray-700" for="professional_id">Profissional</label>
        @php $professionalId = old('professional_id', $appointment->professional_id ?? null); @endphp
        <select class="{{ $input }}" id="professional_id" name="professional_id" required>
            <option value="">Selecione</option>
            @foreach ($professionals as $professional)
                <option value="{{ $professional->id }}" @selected((string) $professionalId === (string) $professional->id)>{{ $professional->display_name }}</option>
            @endforeach
        </select>
        <x-input-error class="mt-1" :messages="$errors->get('professional_id')" />
    </div>

    <div class="md:col-span-6">
        <label class="mb-1 block text-sm font-medium text-gray-700" for="patient_id">Cliente</label>
        @php $patientId = old('patient_id', $appointment->patient_id ?? null); @endphp
        <select class="{{ $input }}" id="patient_id" name="patient_id" required>
            <option value="">Selecione</option>
            @foreach ($patients as $patient)
                <option value="{{ $patient->id }}" @selected((string) $patientId === (string) $patient->id)>{{ $patient->full_name }}</option>
            @endforeach
        </select>
        <x-input-error class="mt-1" :messages="$errors->get('patient_id')" />
    </div>
    <div class="md:col-span-6">
        <label class="mb-1 block text-sm font-medium text-gray-700" for="service_id">Servico</label>
        @php $serviceId = old('service_id', $appointment->service_id ?? null); @endphp
        <select class="{{ $input }}" id="service_id" name="service_id" required>
            <option value="">Selecione</option>
            @foreach ($services as $service)
                @php
                    $servicePrice = $service->price_cents !== null
                        ? number_format($service->price_cents / 100, 2, ',', '.')
                        : '';
                @endphp
                <option value="{{ $service->id }}" data-price="{{ $servicePrice }}" @selected((string) $serviceId === (string) $service->id)>
                    {{ $service->name }}
                </option>
            @endforeach
        </select>
        <x-input-error class="mt-1" :messages="$errors->get('service_id')" />
    </div>

    <div class="md:col-span-3">
        <label class="mb-1 block text-sm font-medium text-gray-700" for="channel">Canal</label>
        <select class="{{ $input }}" id="channel" name="channel" required>
            <option value="presencial" @selected($channelValue === 'presencial')>Presencial</option>
            <option value="home_care" @selected($channelValue === 'home_care')>Home Care</option>
        </select>
        <x-input-error class="mt-1" :messages="$errors->get('channel')" />
    </div>
    <div class="md:col-span-3">
        <label class="mb-1 block text-sm font-medium text-gray-700" for="status">Status</label>
        <select class="{{ $input }}" id="status" name="status" required>
            <option value="agendado" @selected($statusValue === 'agendado')>Agendado</option>
            <option value="confirmado" @selected($statusValue === 'confirmado')>Confirmado</option>
            <option value="atendido" @selected($statusValue === 'atendido')>Atendido</option>
            <option value="concluido" @selected($statusValue === 'concluido')>Concluido</option>
            <option value="cancelado" @selected($statusValue === 'cancelado')>Cancelado</option>
        </select>
        <x-input-error class="mt-1" :messages="$errors->get('status')" />
    </div>
    <div class="md:col-span-3">
        <label class="mb-1 block text-sm font-medium text-gray-700" for="scheduled_at">Data e horario</label>
        <input class="{{ $input }}" type="datetime-local" id="scheduled_at" name="scheduled_at" value="{{ $scheduledValue }}" required />
        <x-input-error class="mt-1" :messages="$errors->get('scheduled_at')" />
    </div>
    <div class="md:col-span-3">
        <label class="mb-1 block text-sm font-medium text-gray-700" for="duration_minutes">Duracao (min)</label>
        <input class="{{ $input }}" type="number" min="5" max="480" id="duration_minutes" name="duration_minutes" value="{{ old('duration_minutes', $appointment->duration_minutes ?? '') }}" placeholder="Ex: 30" />
        <x-input-error class="mt-1" :messages="$errors->get('duration_minutes')" />
    </div>

    <div class="md:col-span-3">
        <label class="mb-1 block text-sm font-medium text-gray-700" for="price">Preco (R$)</label>
        @php
            $priceValue = old('price');
            if ($priceValue === null && isset($appointment->price_cents)) {
                $priceValue = number_format($appointment->price_cents / 100, 2, ',', '.');
            }
        @endphp
        <input class="{{ $input }}" type="text" inputmode="decimal" id="price" name="price" value="{{ $priceValue }}" placeholder="Ex: 80,00" />
        <x-input-error class="mt-1" :messages="$errors->get('price')" />
    </div>
    @if ($isCreate)
        <div class="md:col-span-3">
            <label class="mb-1 block text-sm font-medium text-gray-700" for="recurrence_type">Recorrencia</label>
            @php $recurrenceType = old('recurrence_type', 'none'); @endphp
            <select class="{{ $input }}" id="recurrence_type" name="recurrence_type">
                <option value="none" @selected($recurrenceType === 'none')>Sem recorrencia</option>
                <option value="days" @selected($recurrenceType === 'days')>Por quantidade de dias</option>
                <option value="weekly" @selected($recurrenceType === 'weekly')>Semanal</option>
                <option value="weekly_days" @selected($recurrenceType === 'weekly_days')>Semanal por dias</option>
                <option value="biweekly" @selected($recurrenceType === 'biweekly')>Quinzenal</option>
                <option value="monthly" @selected($recurrenceType === 'monthly')>Mensal</option>
                <option value="semiannual" @selected($recurrenceType === 'semiannual')>Semestral</option>
            </select>
            <x-input-error class="mt-1" :messages="$errors->get('recurrence_type')" />
        </div>
        <div class="md:col-span-3">
            <label class="mb-1 block text-sm font-medium text-gray-700" for="recurrence_occurrences">Qtd. ocorrencias</label>
            <input class="{{ $input }}" type="number" min="1" max="120" id="recurrence_occurrences" name="recurrence_occurrences" value="{{ old('recurrence_occurrences', 1) }}" />
            <x-input-error class="mt-1" :messages="$errors->get('recurrence_occurrences')" />
        </div>
        <div class="md:col-span-3 {{ old('recurrence_type', 'none') === 'days' ? '' : 'hidden' }}" id="recurrence_interval_days_wrapper">
            <label class="mb-1 block text-sm font-medium text-gray-700" for="recurrence_interval_days">Intervalo (dias)</label>
            <input class="{{ $input }}" type="number" min="1" max="365" id="recurrence_interval_days" name="recurrence_interval_days" value="{{ old('recurrence_interval_days', 1) }}" />
            <x-input-error class="mt-1" :messages="$errors->get('recurrence_interval_days')" />
        </div>
        <div class="md:col-span-6 {{ old('recurrence_type', 'none') === 'weekly_days' ? '' : 'hidden' }}" id="recurrence_weekdays_wrapper">
            <div class="mb-1 text-sm font-medium text-gray-700">Dias da semana</div>
            <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
                @foreach ([1 => 'Segunda', 2 => 'Terca', 3 => 'Quarta', 4 => 'Quinta', 5 => 'Sexta', 6 => 'Sabado', 7 => 'Domingo'] as $weekday => $label)
                    <label class="inline-flex items-center gap-2 rounded-lg border border-gray-200 px-3 py-2 text-sm text-gray-600">
                        <input
                            type="checkbox"
                            name="recurrence_weekdays[]"
                            value="{{ $weekday }}"
                            class="h-4 w-4 rounded border-gray-300 text-brand-500 focus:ring-brand-500"
                            @checked(in_array((string) $weekday, $selectedWeekdays, true))
                        />
                        {{ $label }}
                    </label>
                @endforeach
            </div>
            <x-input-error class="mt-1" :messages="$errors->get('recurrence_weekdays')" />
            <x-input-error class="mt-1" :messages="$errors->get('recurrence_weekdays.*')" />
        </div>
    @endif
    <div class="md:col-span-3">
        <label class="mb-1 block text-sm font-medium text-gray-700" for="payment_status">Pagamento</label>
        <select class="{{ $input }}" id="payment_status" name="payment_status">
            <option value="pending" @selected($paymentStatus === 'pending')>Pendente</option>
            <option value="paid" @selected($paymentStatus === 'paid')>Pago</option>
            <option value="refunded" @selected($paymentStatus === 'refunded')>Estornado</option>
        </select>
        <x-input-error class="mt-1" :messages="$errors->get('payment_status')" />
    </div>
    <div class="md:col-span-3 {{ $paymentStatus === 'paid' ? '' : 'hidden' }}" id="payment_method_wrapper">
        <label class="mb-1 block text-sm font-medium text-gray-700" for="forma_pagamento">Forma de pagamento</label>
        <select class="{{ $input }}" id="forma_pagamento" name="forma_pagamento">
            <option value="">Selecione</option>
            <option value="pix" @selected($paymentMethod === 'pix')>Pix</option>
            <option value="cartao" @selected($paymentMethod === 'cartao')>Cartao</option>
            <option value="dinheiro" @selected($paymentMethod === 'dinheiro')>Dinheiro</option>
            <option value="boleto" @selected($paymentMethod === 'boleto')>Boleto</option>
        </select>
        <x-input-error class="mt-1" :messages="$errors->get('forma_pagamento')" />
    </div>
    <div class="md:col-span-3">
        @php $isFirstVisit = old('is_first_visit', $appointment->is_first_visit ?? false); @endphp
        <label class="inline-flex items-center gap-2 text-sm text-gray-600 mt-6">
            <input type="checkbox" name="is_first_visit" value="1" class="h-4 w-4 rounded border-gray-300 text-brand-500 focus:ring-brand-500" @checked($isFirstVisit) />
            Primeiro atendimento
        </label>
    </div>

    <div class="md:col-span-6">
        <label class="mb-1 block text-sm font-medium text-gray-700" for="notes">Observacoes</label>
        <textarea class="{{ $input }}" id="notes" name="notes" rows="3">{{ old('notes', $appointment->notes ?? '') }}</textarea>
        <x-input-error class="mt-1" :messages="$errors->get('notes')" />
    </div>
    <div class="md:col-span-6">
        <label class="mb-1 block text-sm font-medium text-gray-700" for="cancellation_reason">Motivo cancelamento</label>
        <textarea class="{{ $input }}" id="cancellation_reason" name="cancellation_reason" rows="3">{{ old('cancellation_reason', $appointment->cancellation_reason ?? '') }}</textarea>
        <x-input-error class="mt-1" :messages="$errors->get('cancellation_reason')" />
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const serviceSelect = document.getElementById('service_id');
        const priceInput = document.getElementById('price');
        if (!serviceSelect || !priceInput) return;

        const paymentStatusSelect = document.getElementById('payment_status');
        const paymentMethodWrapper = document.getElementById('payment_method_wrapper');
        const paymentMethodSelect = document.getElementById('forma_pagamento');

        const applyServicePrice = () => {
            const selected = serviceSelect.options[serviceSelect.selectedIndex];
            if (!selected) return;
            const price = selected.dataset.price || '';
            if (price) {
                priceInput.value = price;
            }
        };

        serviceSelect.addEventListener('change', applyServicePrice);

        if (!priceInput.value) {
            applyServicePrice();
        }

        const togglePaymentMethod = () => {
            if (!paymentStatusSelect || !paymentMethodWrapper || !paymentMethodSelect) return;
            const isPaid = paymentStatusSelect.value === 'paid';
            paymentMethodWrapper.classList.toggle('hidden', !isPaid);
            paymentMethodSelect.disabled = !isPaid;
            paymentMethodSelect.required = isPaid;
            if (!isPaid) {
                paymentMethodSelect.value = '';
            }
        };

        if (paymentStatusSelect) {
            paymentStatusSelect.addEventListener('change', togglePaymentMethod);
            togglePaymentMethod();
        }

        const recurrenceTypeSelect = document.getElementById('recurrence_type');
        const recurrenceIntervalWrapper = document.getElementById('recurrence_interval_days_wrapper');
        const recurrenceWeekdaysWrapper = document.getElementById('recurrence_weekdays_wrapper');
        const toggleRecurrenceInterval = () => {
            if (!recurrenceTypeSelect) return;
            const showDays = recurrenceTypeSelect.value === 'days';
            const showWeekdays = recurrenceTypeSelect.value === 'weekly_days';
            recurrenceIntervalWrapper?.classList.toggle('hidden', !showDays);
            recurrenceWeekdaysWrapper?.classList.toggle('hidden', !showWeekdays);
        };

        if (recurrenceTypeSelect) {
            recurrenceTypeSelect.addEventListener('change', toggleRecurrenceInterval);
            toggleRecurrenceInterval();
        }
    });
</script>
