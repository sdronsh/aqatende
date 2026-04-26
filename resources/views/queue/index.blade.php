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
                        <option value="{{ $unit->id }}" data-clinic-id="{{ $unit->clinic_id }}" @selected(old('unit_id') == $unit->id)>{{ $unit->name }}</option>
                    @endforeach
                </select>
                <select name="patient_id" class="h-11 w-full rounded-lg border border-gray-200 px-3 text-sm" required>
                    <option value="">Cliente</option>
                    @foreach ($patients as $patient)
                        <option value="{{ $patient->id }}" @selected(old('patient_id') == $patient->id)>{{ $patient->full_name }}</option>
                    @endforeach
                </select>
                <select name="service_id" id="queue_service_id" class="h-11 w-full rounded-lg border border-gray-200 px-3 text-sm" required>
                    <option value="">Serviço</option>
                    @foreach ($services as $service)
                        <option value="{{ $service->id }}" data-clinic-id="{{ $service->clinic_id }}" @selected(old('service_id') == $service->id)>{{ $service->name }} - R$ {{ number_format($service->price_cents / 100, 2, ',', '.') }}</option>
                    @endforeach
                </select>
                <input name="price" class="h-11 w-full rounded-lg border border-gray-200 px-3 text-sm" value="{{ old('price') }}" placeholder="Preço ajustado opcional">
                <textarea name="notes" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm" rows="2" placeholder="Observações">{{ old('notes') }}</textarea>
                <button class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-600">Entrar na fila</button>
            </form>
        </div>

        <div class="space-y-4">
            <div class="rounded-2xl border border-gray-200 bg-white shadow-theme-xs">
                <div class="border-b border-gray-100 px-5 py-4">
                    <h3 class="text-base font-semibold text-gray-800">Aguardando</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-100 text-sm">
                        <thead class="bg-gray-50 text-left text-xs font-semibold uppercase text-gray-500">
                            <tr><th class="px-5 py-3">Entrada</th><th class="px-5 py-3">Cliente</th><th class="px-5 py-3">Serviço</th><th class="px-5 py-3">Valor</th><th class="px-5 py-3">Assumir</th></tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($waiting as $appointment)
                                <tr>
                                    <td class="px-5 py-3 text-gray-600">{{ $appointment->created_at->format('H:i') }}</td>
                                    <td class="px-5 py-3 text-gray-800">{{ $appointment->patient?->full_name }}</td>
                                    <td class="px-5 py-3 text-gray-600">{{ $appointment->service?->name }}</td>
                                    <td class="px-5 py-3 text-gray-600">R$ {{ number_format(($appointment->price_cents ?? 0) / 100, 2, ',', '.') }}</td>
                                    <td class="px-5 py-3">
                                        <form method="POST" action="{{ route('queue.start', $appointment) }}" class="flex min-w-[260px] gap-2">
                                            @csrf
                                            <select name="professional_id" class="h-10 flex-1 rounded-lg border border-gray-200 px-3 text-sm" required>
                                                <option value="">Profissional</option>
                                                @foreach ($professionals as $professional)
                                                    @if ($professional->services->contains('id', $appointment->service_id))
                                                        @php
                                                            $sharedService = (bool) ($appointment->service?->shared_service ?? false);
                                                            $busy = ! $sharedService && in_array((int) $professional->id, $busyProfessionalIds ?? [], true);
                                                        @endphp
                                                        <option value="{{ $professional->id }}" @disabled($busy)>
                                                            {{ $professional->display_name }}{{ $busy ? ' - em atendimento' : ($sharedService && in_array((int) $professional->id, $busyProfessionalIds ?? [], true) ? ' - compartilhado permitido' : '') }}
                                                        </option>
                                                    @endif
                                                @endforeach
                                            </select>
                                            <button class="rounded-lg bg-success-500 px-3 py-2 text-sm font-semibold text-white">Iniciar</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="px-5 py-6 text-center text-gray-500">Fila vazia.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white shadow-theme-xs">
                <div class="border-b border-gray-100 px-5 py-4">
                    <h3 class="text-base font-semibold text-gray-800">Em atendimento</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-100 text-sm">
                        <thead class="bg-gray-50 text-left text-xs font-semibold uppercase text-gray-500">
                            <tr><th class="px-5 py-3">Início</th><th class="px-5 py-3">Cliente</th><th class="px-5 py-3">Serviço</th><th class="px-5 py-3">Profissional</th><th class="px-5 py-3">Finalizar</th></tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($inProgress as $appointment)
                                <tr>
                                    <td class="px-5 py-3 text-gray-600">{{ $appointment->started_at?->format('H:i') }}</td>
                                    <td class="px-5 py-3 text-gray-800">{{ $appointment->patient?->full_name }}</td>
                                    <td class="px-5 py-3 text-gray-600">{{ $appointment->service?->name }}</td>
                                    <td class="px-5 py-3 text-gray-600">{{ $appointment->professional?->display_name }}</td>
                                    <td class="px-5 py-3">
                                        <form method="POST" action="{{ route('queue.finish', $appointment) }}" class="flex min-w-[320px] gap-2">
                                            @csrf
                                            <select name="payment_method" class="h-10 rounded-lg border border-gray-200 px-3 text-sm" required>
                                                <option value="cash">Dinheiro</option>
                                                <option value="pix">Pix</option>
                                                <option value="card">Cartão</option>
                                            </select>
                                            <input name="price" class="h-10 w-28 rounded-lg border border-gray-200 px-3 text-sm" placeholder="{{ number_format(($appointment->price_cents ?? 0) / 100, 2, ',', '.') }}">
                                            <button class="rounded-lg bg-brand-500 px-3 py-2 text-sm font-semibold text-white">Finalizar</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="px-5 py-6 text-center text-gray-500">Nenhum atendimento em andamento.</td></tr>
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
        const serviceSelect = document.getElementById('queue_service_id');
        if (!unitSelect || !serviceSelect) return;

        const syncServices = () => {
            const selectedUnit = unitSelect.options[unitSelect.selectedIndex];
            const clinicId = selectedUnit?.dataset?.clinicId || '';

            Array.from(serviceSelect.options).forEach((option) => {
                if (!option.value) return;
                const matches = !clinicId || option.dataset.clinicId === clinicId;
                option.hidden = !matches;
                option.disabled = !matches;
            });

            const current = serviceSelect.options[serviceSelect.selectedIndex];
            if (current?.disabled) {
                serviceSelect.value = '';
            }
        };

        unitSelect.addEventListener('change', syncServices);
        syncServices();
    });
</script>
