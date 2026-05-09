<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-lg font-semibold text-gray-800">Fila de atendimento</h2>
            <p class="text-sm text-gray-500">Entrada sem agendamento, início e finalização do serviço.</p>
        </div>
    </x-slot>

    @if ($errors->any())
        <div class="mb-4 rounded-xl border border-error-200 bg-error-50 px-4 py-3 text-sm text-error-700">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="grid gap-4 xl:grid-cols-[380px_1fr]">
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-xs">
            <h3 class="text-base font-semibold text-gray-800">Adicionar à fila</h3>
            <form method="POST" action="{{ route('queue.store') }}" class="mt-4 space-y-3">
                @csrf
                <select name="unit_id" id="queue_unit_id" class="h-11 w-full rounded-lg border border-gray-200 px-3 text-sm" required>
                    <option value="">Unidade</option>
                    @foreach ($units as $unit)
                        <option value="{{ $unit->id }}" data-clinic-id="{{ $unit->clinic_id }}" @selected((string) old('unit_id', $units->count() === 1 ? $units->first()->id : '') === (string) $unit->id)>{{ $unit->name }}</option>
                    @endforeach
                </select>
                <select name="patient_id" class="h-11 w-full rounded-lg border border-gray-200 px-3 text-sm" required>
                    <option value="">Cliente</option>
                    @foreach ($patients as $patient)
                        <option value="{{ $patient->id }}" @selected(old('patient_id') == $patient->id)>{{ $patient->full_name }}</option>
                    @endforeach
                </select>
                <div id="queue_service_id" class="max-h-56 w-full overflow-y-auto rounded-lg border border-gray-200 bg-white shadow-theme-xs">
                    @foreach ($services as $service)
                        @php $servicePrice = number_format($service->price_cents / 100, 2, ',', '.'); @endphp
                        <div
                            class="grid gap-2 border-b border-gray-100 px-3 py-2 text-sm last:border-b-0 hover:bg-gray-50 md:grid-cols-[1fr_220px]"
                            data-clinic-id="{{ $service->clinic_id }}"
                            data-queue-service-row
                        >
                            <label class="flex cursor-pointer items-center gap-3">
                            <input
                                type="checkbox"
                                name="service_ids[]"
                                value="{{ $service->id }}"
                                class="h-4 w-4 rounded border-gray-300 text-brand-500 focus:ring-brand-500"
                                data-queue-service-option
                                data-clinic-id="{{ $service->clinic_id }}"
                                data-price="{{ $servicePrice }}"
                                @checked(collect(old('service_ids', []))->contains($service->id) || old('service_id') == $service->id)
                            />
                            <span class="flex-1 text-gray-700">{{ $service->name }}</span>
                            <span class="text-xs text-gray-500">R$ {{ $servicePrice }}</span>
                            </label>
                            <select class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 shadow-theme-xs" name="service_professional_ids[{{ $service->id }}]">
                                <option value="">Definir ao iniciar</option>
                                @foreach ($professionals as $professional)
                                    @if ($professional->services->contains('id', $service->id))
                                        <option value="{{ $professional->id }}" @selected((string) old("service_professional_ids.{$service->id}") === (string) $professional->id)>
                                            {{ $professional->display_name }}
                                        </option>
                                    @endif
                                @endforeach
                            </select>
                        </div>
                    @endforeach
                </div>
                <input name="price" id="queue_price" class="h-11 w-full rounded-lg border border-gray-200 px-3 text-sm" value="{{ old('price') }}" placeholder="Preço ajustado opcional">
                <textarea name="notes" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm" rows="2" placeholder="Observações">{{ old('notes') }}</textarea>
                <button class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-600">Entrar na fila</button>
            </form>
        </div>

        <div class="space-y-4">
            <div class="rounded-2xl border border-gray-200 bg-white shadow-theme-xs">
                <div class="border-b border-gray-100 px-5 py-4">
                    <h3 class="text-base font-semibold text-gray-800">Aguardando</h3>
                </div>
                <div class="responsive-table-wrapper overflow-x-auto">
                    <table class="responsive-table min-w-full divide-y divide-gray-100 text-sm">
                        <thead class="bg-gray-50 text-left text-xs font-semibold uppercase text-gray-500">
                            <tr><th class="px-5 py-3">Entrada</th><th class="px-5 py-3">Cliente</th><th class="px-5 py-3">Serviço</th><th class="px-5 py-3">Valor</th><th class="px-5 py-3">Assumir</th></tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($waiting as $appointment)
                                <tr>
                                    <td class="px-5 py-3 text-gray-600" data-label="Entrada">{{ $appointment->created_at->format('H:i') }}</td>
                                    <td class="px-5 py-3 text-gray-800" data-label="Cliente">{{ $appointment->patient?->full_name }}</td>
                                    <td class="px-5 py-3 text-gray-600" data-label="Serviço">{{ $appointment->serviceNames() }}</td>
                                    <td class="px-5 py-3 text-gray-600" data-label="Valor">R$ {{ number_format(($appointment->price_cents ?? 0) / 100, 2, ',', '.') }}</td>
                                    <td class="px-5 py-3" data-actions>
                                        <form method="POST" action="{{ route('queue.start', $appointment) }}" class="grid gap-2 md:min-w-[280px]">
                                            @csrf
                                            @php
                                                $appointmentServices = $appointment->services->isNotEmpty()
                                                    ? $appointment->services
                                                    : collect([$appointment->service])->filter();
                                            @endphp
                                            <div class="grid gap-2">
                                                @foreach ($appointmentServices as $service)
                                                    @php
                                                        $selectedProfessionalId = old("service_professional_ids.{$service->id}", $service->pivot?->professional_id);
                                                    @endphp
                                                    <label class="grid gap-1 text-xs font-medium text-gray-600">
                                                        <span>{{ $service->name }}</span>
                                                        <select name="service_professional_ids[{{ $service->id }}]" class="h-10 rounded-lg border border-gray-200 px-3 text-sm font-normal text-gray-700">
                                                            <option value="">Selecione profissional</option>
                                                            @foreach ($professionals as $professional)
                                                                @if ($professional->services->contains('id', $service->id))
                                                                    @php
                                                                        $busy = ! $service->shared_service && in_array((int) $professional->id, $busyProfessionalIds ?? [], true);
                                                                    @endphp
                                                                    <option value="{{ $professional->id }}" @selected((string) $selectedProfessionalId === (string) $professional->id) @disabled($busy)>
                                                                        {{ $professional->display_name }}{{ $busy ? ' - em atendimento' : ($service->shared_service && in_array((int) $professional->id, $busyProfessionalIds ?? [], true) ? ' - compartilhado permitido' : '') }}
                                                                    </option>
                                                                @endif
                                                            @endforeach
                                                        </select>
                                                    </label>
                                                @endforeach
                                            </div>
                                            <button class="rounded-lg bg-success-500 px-3 py-2 text-sm font-semibold text-white">Iniciar</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="px-5 py-6 text-center text-gray-500" data-empty>Fila vazia.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white shadow-theme-xs">
                <div class="border-b border-gray-100 px-5 py-4">
                    <h3 class="text-base font-semibold text-gray-800">Em atendimento</h3>
                </div>
                <div class="responsive-table-wrapper overflow-x-auto">
                    <table class="responsive-table min-w-full divide-y divide-gray-100 text-sm">
                        <thead class="bg-gray-50 text-left text-xs font-semibold uppercase text-gray-500">
                            <tr><th class="px-5 py-3">Início</th><th class="px-5 py-3">Cliente</th><th class="px-5 py-3">Serviço</th><th class="px-5 py-3">Profissional</th><th class="px-5 py-3">Finalizar</th></tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($inProgress as $appointment)
                                <tr>
                                    <td class="px-5 py-3 text-gray-600" data-label="Início">{{ $appointment->started_at?->format('H:i') }}</td>
                                    <td class="px-5 py-3 text-gray-800" data-label="Cliente">{{ $appointment->patient?->full_name }}</td>
                                    <td class="px-5 py-3 text-gray-600" data-label="Serviço">{{ $appointment->serviceNames() }}</td>
                                    <td class="px-5 py-3 text-gray-600" data-label="Profissional">
                                        @php
                                            $appointmentServices = $appointment->services->isNotEmpty()
                                                ? $appointment->services
                                                : collect([$appointment->service])->filter();
                                        @endphp
                                        @if ($appointmentServices->isNotEmpty())
                                            <div class="space-y-1">
                                                @foreach ($appointmentServices as $service)
                                                    @php
                                                        $serviceProfessional = $service->pivot?->professional_id
                                                            ? $professionals->firstWhere('id', $service->pivot->professional_id)
                                                            : $appointment->professional;
                                                    @endphp
                                                    <div>
                                                        <span class="font-medium text-gray-700">{{ $service->name }}:</span>
                                                        {{ $serviceProfessional?->display_name ?? '-' }}
                                                    </div>
                                                @endforeach
                                            </div>
                                        @else
                                            {{ $appointment->professional?->display_name }}
                                        @endif
                                    </td>
                                    <td class="px-5 py-3" data-actions>
                                        <form method="POST" action="{{ route('queue.finish', $appointment) }}" class="grid gap-2 sm:flex md:min-w-[320px]">
                                            @csrf
                                            <select name="payment_method" class="h-10 rounded-lg border border-gray-200 px-3 text-sm" required>
                                                <option value="cash">Dinheiro</option>
                                                <option value="pix">Pix</option>
                                                <option value="card">Cartão</option>
                                            </select>
                                            <input name="price" class="h-10 w-full rounded-lg border border-gray-200 px-3 text-sm sm:w-28" placeholder="{{ number_format(($appointment->price_cents ?? 0) / 100, 2, ',', '.') }}">
                                            <button class="rounded-lg bg-brand-500 px-3 py-2 text-sm font-semibold text-white">Finalizar</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="px-5 py-6 text-center text-gray-500" data-empty>Nenhum atendimento em andamento.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const unitSelect = document.getElementById('queue_unit_id');
        const serviceRows = Array.from(document.querySelectorAll('[data-queue-service-row]'));
        const serviceOptions = Array.from(document.querySelectorAll('[data-queue-service-option]'));
        const priceInput = document.getElementById('queue_price');
        if (!unitSelect || !serviceOptions.length) return;

        const moneyToNumber = (value) => Number((value || '0').replace(/\./g, '').replace(',', '.')) || 0;
        const numberToMoney = (value) => value.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

        const syncPrice = () => {
            if (!priceInput) return;
            const selected = serviceOptions.filter((option) => option.checked);
            const price = selected.reduce((sum, option) => sum + moneyToNumber(option.dataset.price), 0);
            if (price > 0) {
                priceInput.value = numberToMoney(price);
            }
        };

        const syncServices = () => {
            const selectedUnit = unitSelect.options[unitSelect.selectedIndex];
            const clinicId = selectedUnit?.dataset?.clinicId || '';

            serviceRows.forEach((row) => {
                const matches = !clinicId || row.dataset.clinicId === clinicId;
                row.classList.toggle('hidden', !matches);
            });

            serviceOptions.forEach((option) => {
                const matches = !clinicId || option.dataset.clinicId === clinicId;
                option.disabled = !matches;
                if (!matches) {
                    option.checked = false;
                }
            });
            syncPrice();
        };

        unitSelect.addEventListener('change', syncServices);
        serviceOptions.forEach((option) => option.addEventListener('change', syncPrice));
        syncServices();
        if (!priceInput?.value) {
            syncPrice();
        }
    });
</script>
