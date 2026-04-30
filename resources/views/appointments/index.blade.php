<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <div class="text-xs uppercase text-gray-400">Agendamento</div>
                <h2 class="text-lg font-semibold text-gray-800">Agendamentos</h2>
            </div>
            @php
                $canCreate = auth()->user()->is_platform_admin
                    || auth()->user()->hasCompanyPermission(session('active_company_id'), 'agendamento.agendamentos.create');
            @endphp
            @if ($canCreate)
                <a class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white shadow-theme-xs hover:bg-brand-600" href="{{ route('appointments.create') }}">+ Novo</a>
            @endif
        </div>
    </x-slot>

    <div class="rounded-xl border border-gray-200 bg-white shadow-theme-sm">
        <div class="border-b border-gray-200 px-6 py-4">
            <form method="GET" action="{{ route('appointments.index') }}" class="grid gap-3 md:grid-cols-12">
                <div class="md:col-span-3">
                    <input class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 shadow-theme-xs focus:border-brand-300 focus:outline-none focus:ring-3 focus:ring-brand-500/10" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Buscar cliente/profissional/servico" />
                </div>
                <div class="md:col-span-2">
                    <input class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 shadow-theme-xs focus:border-brand-300 focus:outline-none focus:ring-3 focus:ring-brand-500/10" type="date" name="date" value="{{ $filters['date'] ?? '' }}" />
                </div>
                <div class="md:col-span-2">
                    <select class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 shadow-theme-xs focus:border-brand-300 focus:outline-none focus:ring-3 focus:ring-brand-500/10" name="unit_id">
                        <option value="">Todas as unidades</option>
                        @foreach ($units as $unit)
                            <option value="{{ $unit->id }}" @selected((string) ($filters['unit_id'] ?? '') === (string) $unit->id)>{{ $unit->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="md:col-span-3">
                    <select class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 shadow-theme-xs focus:border-brand-300 focus:outline-none focus:ring-3 focus:ring-brand-500/10" name="professional_id">
                        <option value="">Todos os profissionais</option>
                        @foreach ($professionals as $professional)
                            <option value="{{ $professional->id }}" @selected((string) ($filters['professional_id'] ?? '') === (string) $professional->id)>{{ $professional->display_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="md:col-span-3">
                    <select class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 shadow-theme-xs focus:border-brand-300 focus:outline-none focus:ring-3 focus:ring-brand-500/10" name="patient_id">
                        <option value="">Todos os clientes</option>
                        @foreach ($patients as $patient)
                            <option value="{{ $patient->id }}" @selected((string) ($filters['patient_id'] ?? '') === (string) $patient->id)>{{ $patient->full_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="md:col-span-2">
                    <select class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 shadow-theme-xs focus:border-brand-300 focus:outline-none focus:ring-3 focus:ring-brand-500/10" name="status">
                        <option value="">Todos os status</option>
                        @foreach (['agendado' => 'Agendado', 'confirmado' => 'Confirmado', 'atendido' => 'Atendido', 'concluido' => 'Concluido', 'cancelado' => 'Cancelado'] as $value => $label)
                            <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="md:col-span-2">
                    <select class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 shadow-theme-xs focus:border-brand-300 focus:outline-none focus:ring-3 focus:ring-brand-500/10" name="channel">
                        <option value="">Todos os canais</option>
                        <option value="presencial" @selected(($filters['channel'] ?? '') === 'presencial')>Presencial</option>
                        <option value="home_care" @selected(($filters['channel'] ?? '') === 'home_care')>Home Care</option>
                    </select>
                </div>
                <div class="md:col-span-1">
                    <select class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 shadow-theme-xs focus:border-brand-300 focus:outline-none focus:ring-3 focus:ring-brand-500/10" name="per_page">
                        <option value="10" @selected($perPage === '10')>10</option>
                        <option value="20" @selected($perPage === '20')>20</option>
                        <option value="50" @selected($perPage === '50')>50</option>
                        <option value="all" @selected($perPage === 'all')>Todos</option>
                    </select>
                </div>
                <div class="md:col-span-2">
                    <select class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 shadow-theme-xs focus:border-brand-300 focus:outline-none focus:ring-3 focus:ring-brand-500/10" name="order_by">
                        <option value="date_desc" @selected(($filters['order_by'] ?? 'date_asc') === 'date_desc')>Data (mais recente)</option>
                        <option value="date_asc" @selected(($filters['order_by'] ?? '') === 'date_asc')>Data (mais antiga)</option>
                    </select>
                </div>
                <div class="md:col-span-1">
                    <button class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-theme-xs hover:bg-gray-50" type="submit">Buscar</button>
                </div>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full border-separate border border-gray-200 [border-spacing:0] text-sm">
                <thead class="bg-gray-50">
                    <tr class="text-left text-xs font-semibold uppercase text-gray-500">
                        <th class="border border-gray-200 px-4 py-3">Data</th>
                        <th class="border border-gray-200 px-4 py-3">Profissional</th>
                        <th class="border border-gray-200 px-4 py-3">Cliente</th>
                        <th class="border border-gray-200 px-4 py-3">Servico</th>
                        <th class="border border-gray-200 px-4 py-3">Canal</th>
                        <th class="border border-gray-200 px-4 py-3">Status</th>
                        <th class="border border-gray-200 px-4 py-3 text-right">Acoes</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($appointments as $appointment)
                        @php
                            $rawStatus = strtolower($appointment->status ?? 'agendado');
                            $statusMap = [
                                'scheduled' => 'agendado',
                                'confirmed' => 'confirmado',
                                'attended' => 'atendido',
                                'done' => 'concluido',
                                'cancelled' => 'cancelado',
                            ];
                            $status = $statusMap[$rawStatus] ?? $rawStatus;
                            $statusClass = match ($status) {
                                'cancelado' => 'bg-error-50 text-error-700 border-error-200',
                                'concluido', 'atendido' => 'bg-success-50 text-success-700 border-success-200',
                                'confirmado' => 'bg-brand-50 text-brand-800 border-brand-200',
                                default => 'bg-warning-50 text-warning-900 border-warning-200',
                            };
                            $recurrence = $recurrenceMeta[$appointment->id] ?? ['is_recurring' => false, 'has_future' => false, 'future_count' => 0];
                        @endphp
                        <tr class="odd:bg-gray-50">
                            <td class="border border-gray-200 px-4 py-3 font-medium text-gray-800">
                                {{ optional($appointment->scheduled_at)->format('d/m/Y H:i') }}
                            </td>
                            <td class="border border-gray-200 px-4 py-3 text-gray-600">{{ $appointment->professional?->display_name }}</td>
                            <td class="border border-gray-200 px-4 py-3 text-gray-600">{{ $appointment->patient?->full_name }}</td>
                            <td class="border border-gray-200 px-4 py-3 text-gray-600">{{ $appointment->serviceNames() }}</td>
                            <td class="border border-gray-200 px-4 py-3 text-gray-600">
                                {{ ['presencial' => 'Presencial', 'home_care' => 'Home Care', 'whatsapp' => 'Home Care', 'teleconsulta' => 'Home Care', 'walk_in' => 'Fila'][$appointment->channel ?? 'presencial'] ?? ucfirst($appointment->channel ?? 'presencial') }}
                            </td>
                            <td class="border border-gray-200 px-4 py-3">
                                <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-xs {{ $statusClass }}">
                                    {{ ucfirst($status) }}
                                </span>
                            </td>
                            <td class="border border-gray-200 px-4 py-3">
                                <div class="flex justify-end gap-2">
                                    <a class="rounded-lg border border-brand-500 px-2 py-1 text-xs font-medium text-brand-500 hover:bg-brand-50" href="{{ route('appointments.edit', $appointment) }}">Editar</a>
                                    <form
                                        method="POST"
                                        action="{{ route('appointments.destroy', $appointment) }}"
                                        data-delete-appointment-form
                                        data-has-future-recurrences="{{ $recurrence['has_future'] ? '1' : '0' }}"
                                        data-future-count="{{ $recurrence['future_count'] ?? 0 }}"
                                    >
                                        @csrf
                                        @method('DELETE')
                                        <input type="hidden" name="delete_future_recurrences" value="0" data-delete-future-input />
                                        <button class="rounded-lg border border-error-500 px-2 py-1 text-xs font-medium text-error-500 hover:bg-error-50" type="submit">Excluir</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="border border-gray-200 px-6 py-6 text-center text-gray-500">Nenhum agendamento encontrado.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if (method_exists($appointments, 'links'))
            <div class="border-t border-gray-200 px-6 py-3">
                {{ $appointments->links() }}
            </div>
        @endif
    </div>

    <dialog id="delete-confirm-dialog" class="m-auto w-full max-w-md rounded-xl border border-gray-200 p-0 shadow-theme-lg">
        <div class="flex flex-col gap-4 p-5">
            <div class="text-lg font-semibold text-gray-800">Excluir agendamento</div>
            <p class="text-sm text-gray-600">
                Confirma a exclusao deste agendamento?
            </p>
            <div class="flex items-center justify-end gap-2 border-t border-gray-100 pt-4">
                <button type="button" id="delete-confirm-no" class="rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-theme-xs hover:bg-gray-50">
                    Nao
                </button>
                <button type="button" id="delete-confirm-yes" class="rounded-lg bg-error-500 px-4 py-2 text-sm font-semibold text-white shadow-theme-xs hover:bg-error-600">
                    Sim
                </button>
            </div>
        </div>
    </dialog>

    <dialog id="delete-recurrence-dialog" class="m-auto w-full max-w-md rounded-xl border border-gray-200 p-0 shadow-theme-lg">
        <div class="flex flex-col gap-4 p-5">
            <div class="text-lg font-semibold text-gray-800">Excluir agendamento</div>
            <p id="delete-recurrence-message" class="text-sm text-gray-600">
                Este agendamento possui recorrencias futuras. Deseja excluir tambem as recorrencias futuras?
            </p>
            <div class="flex items-center justify-end gap-2 border-t border-gray-100 pt-4">
                <button type="button" id="delete-recurrence-no" class="rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-theme-xs hover:bg-gray-50">
                    Nao
                </button>
                <button type="button" id="delete-recurrence-yes" class="rounded-lg bg-error-500 px-4 py-2 text-sm font-semibold text-white shadow-theme-xs hover:bg-error-600">
                    Sim
                </button>
            </div>
        </div>
    </dialog>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const confirmDialog = document.getElementById('delete-confirm-dialog');
            const confirmNo = document.getElementById('delete-confirm-no');
            const confirmYes = document.getElementById('delete-confirm-yes');
            const recurrenceDialog = document.getElementById('delete-recurrence-dialog');
            const recurrenceNo = document.getElementById('delete-recurrence-no');
            const recurrenceYes = document.getElementById('delete-recurrence-yes');
            const message = document.getElementById('delete-recurrence-message');
            let pendingForm = null;

            const closeAllDialogs = () => {
                if (confirmDialog?.open) confirmDialog.close();
                if (recurrenceDialog?.open) recurrenceDialog.close();
                pendingForm = null;
            };

            document.querySelectorAll('[data-delete-appointment-form]').forEach((form) => {
                form.addEventListener('submit', (event) => {
                    event.preventDefault();
                    pendingForm = form;
                    confirmDialog?.showModal();
                    confirmNo?.focus();
                });
            });

            confirmNo?.addEventListener('click', () => {
                closeAllDialogs();
            });

            confirmYes?.addEventListener('click', () => {
                if (!pendingForm) {
                    closeAllDialogs();
                    return;
                }

                if (confirmDialog?.open) confirmDialog.close();

                const hasFuture = pendingForm.dataset.hasFutureRecurrences === '1';
                if (!hasFuture || !recurrenceDialog) {
                    const hiddenInput = pendingForm.querySelector('[data-delete-future-input]');
                    if (hiddenInput) hiddenInput.value = '0';
                    pendingForm.submit();
                    pendingForm = null;
                    return;
                }

                const futureCount = Number(pendingForm.dataset.futureCount || '0');
                if (message) {
                    message.textContent = futureCount > 0
                        ? `Este agendamento possui ${futureCount} recorrencia(s) futura(s). Deseja excluir tambem as recorrencias futuras?`
                        : 'Este agendamento possui recorrencias futuras. Deseja excluir tambem as recorrencias futuras?';
                }

                recurrenceDialog.showModal();
                recurrenceNo?.focus();
            });

            recurrenceNo?.addEventListener('click', () => {
                if (pendingForm) {
                    const hiddenInput = pendingForm.querySelector('[data-delete-future-input]');
                    if (hiddenInput) hiddenInput.value = '0';
                    pendingForm.submit();
                }
                closeAllDialogs();
            });

            recurrenceYes?.addEventListener('click', () => {
                if (pendingForm) {
                    const hiddenInput = pendingForm.querySelector('[data-delete-future-input]');
                    if (hiddenInput) hiddenInput.value = '1';
                    pendingForm.submit();
                }
                closeAllDialogs();
            });

            confirmDialog?.addEventListener('click', (event) => {
                if (event.target === confirmDialog) {
                    closeAllDialogs();
                }
            });

            recurrenceDialog?.addEventListener('click', (event) => {
                if (event.target === recurrenceDialog) {
                    closeAllDialogs();
                }
            });
        });
    </script>
</x-app-layout>
