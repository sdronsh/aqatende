@php
    $input = 'rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 shadow-theme-xs focus:border-brand-300 focus:outline-none focus:ring-3 focus:ring-brand-500/10';
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-lg font-semibold text-gray-800">Atendimentos</h2>
            <div class="text-xs text-gray-500">Atualiza automaticamente a cada 60s</div>
        </div>
    </x-slot>

    <div class="space-y-4">
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-theme-sm">
            <form method="GET" class="flex flex-wrap items-end gap-3">
                <div>
                    <label class="mb-1 block text-xs font-medium uppercase text-gray-400" for="date">Data</label>
                    <input class="{{ $input }}" type="date" id="date" name="date" value="{{ $date->toDateString() }}" />
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium uppercase text-gray-400" for="clinic_id">Clinica</label>
                    <select class="{{ $input }}" id="clinic_id" name="clinic_id">
                        <option value="">Todas as clinicas</option>
                        @foreach ($clinics as $clinic)
                            <option value="{{ $clinic->id }}" @selected($selectedClinicId === $clinic->id)>
                                {{ $clinic->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium uppercase text-gray-400" for="professional_id">Profissional</label>
                    @if ($lockProfessionalFilter)
                        <input type="hidden" name="professional_id" value="{{ $selectedProfessionalId }}" />
                    @endif
                    <select class="{{ $input }} {{ $lockProfessionalFilter ? 'bg-gray-100 text-gray-500' : '' }}" id="professional_id" name="professional_id" @disabled($lockProfessionalFilter)>
                        <option value="">Todos os profissionais</option>
                        @foreach ($professionals as $professional)
                            <option value="{{ $professional->id }}" @selected($selectedProfessionalId === $professional->id)>
                                {{ $professional->display_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <button class="rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-theme-xs hover:bg-gray-50" type="submit">
                    Aplicar
                </button>
            </form>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white shadow-theme-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full border-separate border-spacing-0 text-sm">
                    <thead>
                        <tr class="text-left text-xs uppercase text-gray-400">
                            <th class="border-b border-gray-200 px-4 py-3">Horario</th>
                            <th class="border-b border-gray-200 px-4 py-3">Cliente</th>
                            <th class="border-b border-gray-200 px-4 py-3">Profissional</th>
                            <th class="border-b border-gray-200 px-4 py-3">Servico</th>
                            <th class="border-b border-gray-200 px-4 py-3">Clinica</th>
                            <th class="border-b border-gray-200 px-4 py-3">Unidade</th>
                            <th class="border-b border-gray-200 px-4 py-3">Status</th>
                            <th class="border-b border-gray-200 px-4 py-3">Pagamento</th>
                            <th class="border-b border-gray-200 px-4 py-3 text-right">Acoes</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($appointments as $appointment)
                            @php
                                $status = strtolower($appointment->status ?? 'agendado');
                                $statusMap = [
                                    'scheduled' => 'agendado',
                                    'confirmed' => 'confirmado',
                                    'attended' => 'atendido',
                                    'done' => 'concluido',
                                    'cancelled' => 'cancelado',
                                ];
                                $status = $statusMap[$status] ?? $status;
                            @endphp
                            <tr class="border-b border-gray-100 last:border-b-0">
                                <td class="px-4 py-3 text-gray-700">{{ $appointment->scheduled_at->format('H:i') }}</td>
                                <td class="px-4 py-3 text-gray-700">{{ $appointment->patient?->full_name ?? 'Cliente' }}</td>
                                <td class="px-4 py-3 text-gray-700">{{ $appointment->professional?->display_name ?? '-' }}</td>
                                <td class="px-4 py-3 text-gray-700">{{ $appointment->serviceNames() }}</td>
                                <td class="px-4 py-3 text-gray-700">{{ $appointment->clinic?->name ?? '-' }}</td>
                                <td class="px-4 py-3 text-gray-700">{{ $appointment->unit?->name ?? '-' }}</td>
                                <td class="px-4 py-3 text-gray-700">{{ ucfirst($status) }}</td>
                                <td class="px-4 py-3 text-gray-700">
                                    {{ $appointment->payment_status === 'paid' ? 'Pago' : 'Pendente' }}
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <a class="rounded-lg border border-gray-200 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50" href="{{ route('attendance.record.edit', $appointment) }}">
                                        Abrir
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td class="px-4 py-6 text-sm text-gray-500" colspan="9">Nenhum atendimento para este dia.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        setInterval(() => {
            window.location.reload();
        }, 60000);
    });
</script>
