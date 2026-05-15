<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <div class="text-xs uppercase text-gray-400">Cadastro</div>
                <h2 class="text-lg font-semibold text-gray-800">Clientes</h2>
            </div>
            <a class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white shadow-theme-xs hover:bg-brand-600" href="{{ route('patients.create') }}">+ Novo</a>
        </div>
    </x-slot>

    @if (session('booking_link'))
        <div class="mb-4 rounded-xl border border-brand-200 bg-brand-50 p-4 text-sm text-brand-900 shadow-theme-sm">
            <div class="font-semibold">Link de agendamento gerado</div>
            <div class="mt-2 flex flex-col gap-2 sm:flex-row">
                <input id="booking-link-input" class="w-full rounded-lg border border-brand-200 bg-white px-3 py-2 text-sm text-gray-700" value="{{ session('booking_link') }}" readonly onclick="this.select()" />
                <a class="inline-flex items-center justify-center rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700" href="{{ session('booking_link') }}" target="_blank" rel="noopener">Abrir</a>
                <button class="inline-flex items-center justify-center rounded-lg border border-brand-600 bg-white px-4 py-2 text-sm font-semibold text-brand-700 hover:bg-white/70" type="button" data-copy-booking-link data-target="booking-link-input">Copiar</button>
            </div>
            <p class="mt-2 text-xs text-brand-700">Copie este link e envie para o cliente pelo WhatsApp. Ele expira em 7 dias ou apos o primeiro agendamento.</p>
        </div>
    @endif

    <style>
        @media (max-width: 767px) {
            .patients-mobile-hidden {
                display: none !important;
            }
        }
    </style>

    <div class="flex flex-col overflow-visible rounded-xl border border-gray-200 bg-white shadow-theme-sm md:max-h-[calc(100vh-9rem)] md:overflow-hidden">
        <div class="sticky top-0 z-10 flex shrink-0 flex-wrap items-center justify-between gap-3 border-b border-gray-200 bg-white px-6 py-4">
            <div class="text-sm font-medium text-gray-700">Lista de Clientes</div>
            <form method="GET" action="{{ route('patients.index') }}" class="flex w-full max-w-3xl flex-wrap items-center gap-2">
                <input class="w-full flex-1 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 shadow-theme-xs focus:border-brand-300 focus:outline-none focus:ring-3 focus:ring-brand-500/10" name="search" value="{{ request('search') }}" placeholder="Buscar por nome, CPF ou telefone" />
                <select class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 shadow-theme-xs focus:border-brand-300 focus:outline-none focus:ring-3 focus:ring-brand-500/10" name="per_page">
                    @php $selectedPerPage = $perPage ?? request('per_page', '10'); @endphp
                    <option value="10" @selected($selectedPerPage === '10')>10</option>
                    <option value="20" @selected($selectedPerPage === '20')>20</option>
                    <option value="50" @selected($selectedPerPage === '50')>50</option>
                    <option value="all" @selected($selectedPerPage === 'all')>Todos</option>
                </select>
                <button class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-theme-xs hover:bg-gray-50" type="submit">Buscar</button>
            </form>
        </div>
        <div class="responsive-table-wrapper min-h-0 flex-1 overflow-visible md:overflow-auto">
            <table class="responsive-table min-w-full border-separate border border-gray-200 [border-spacing:0] text-sm">
                <thead class="bg-gray-50">
                    <tr class="text-left text-xs font-semibold uppercase text-gray-500">
                        <th class="border border-gray-200 px-4 py-3">Nome</th>
                        <th class="patients-mobile-hidden border border-gray-200 px-4 py-3">CPF</th>
                        <th class="patients-mobile-hidden border border-gray-200 px-4 py-3">Nascimento</th>
                        <th class="patients-mobile-hidden border border-gray-200 px-4 py-3">Sexo</th>
                        <th class="patients-mobile-hidden border border-gray-200 px-4 py-3">Telefone</th>
                        <th class="patients-mobile-hidden border border-gray-200 px-4 py-3">Celular</th>
                        <th class="patients-mobile-hidden border border-gray-200 px-4 py-3">Ultimo atendimento</th>
                        <th class="border border-gray-200 px-4 py-3 text-right">Acoes</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($patients as $patient)
                        @php
                            $displayName = $patient->social_name ?: $patient->full_name;
                        @endphp
                        <tr class="odd:bg-gray-50">
                            <td class="border border-gray-200 px-4 py-3 font-medium text-gray-800" data-label="Nome">
                                <span class="md:hidden">{{ $displayName }}</span>
                                <span class="hidden md:block">{{ $patient->full_name }}</span>
                                @if ($patient->social_name)
                                    <span class="hidden text-xs font-normal text-gray-500 md:block">{{ $patient->social_name }}</span>
                                @endif
                            </td>
                            <td class="patients-mobile-hidden border border-gray-200 px-4 py-3 text-gray-600" data-label="CPF">{{ $patient->cpf ?? '-' }}</td>
                            <td class="patients-mobile-hidden border border-gray-200 px-4 py-3 text-gray-600" data-label="Nascimento">
                                {{ $patient->birthdate ? $patient->birthdate->format('d/m/Y') : '-' }}
                            </td>
                            <td class="patients-mobile-hidden border border-gray-200 px-4 py-3 text-gray-600" data-label="Sexo">{{ ucfirst($patient->gender ?? '-') }}</td>
                            <td class="patients-mobile-hidden border border-gray-200 px-4 py-3 text-gray-600" data-label="Telefone">{{ $patient->phone ?? '-' }}</td>
                            <td class="patients-mobile-hidden border border-gray-200 px-4 py-3 text-gray-600" data-label="Celular">{{ $patient->cellphone ?? '-' }}</td>
                            <td class="patients-mobile-hidden border border-gray-200 px-4 py-3 text-gray-600" data-label="Ultimo atendimento">
                                @if ($patient->last_appointment_at)
                                    {{ \Illuminate\Support\Carbon::parse($patient->last_appointment_at)->format('d/m/Y H:i') }}
                                @else
                                    -
                                @endif
                            </td>
                            <td class="border border-gray-200 px-4 py-3" data-actions>
                                <div class="flex justify-end gap-2">
                                    <a class="rounded-lg border border-brand-500 px-2 py-1 text-xs font-medium text-brand-500 hover:bg-brand-50" href="{{ route('patients.edit', $patient) }}">Editar</a>
                                    <form method="POST" action="{{ route('patients.booking-link', $patient) }}">
                                        @csrf
                                        <button class="rounded-lg border border-success-600 px-2 py-1 text-xs font-medium text-success-700 hover:bg-success-50" type="submit">Gerar link</button>
                                    </form>
                                    <form
                                        method="POST"
                                        action="{{ route('patients.destroy', $patient) }}"
                                        data-delete-patient-form
                                        data-has-appointments="{{ $patient->has_appointments ? '1' : '0' }}"
                                    >
                                        @csrf
                                        @method('DELETE')
                                        <button class="rounded-lg border border-error-500 px-2 py-1 text-xs font-medium text-error-500 hover:bg-error-50" type="submit">Excluir</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="border border-gray-200 px-6 py-6 text-center text-gray-500" data-empty>Nenhum cliente cadastrado.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if (method_exists($patients, 'links'))
            <div class="border-t border-gray-200 px-6 py-3">
                {{ $patients->links() }}
            </div>
        @endif
    </div>

    <dialog id="delete-patient-dialog" class="m-auto max-h-[90vh] w-[calc(100%-2rem)] max-w-md overflow-y-auto rounded-xl border border-gray-200 p-0 shadow-theme-lg">
        <div class="flex flex-col gap-4 p-5">
            <div class="text-lg font-semibold text-gray-800">Excluir cliente</div>
            <p id="delete-patient-message" class="text-sm text-gray-600">
                Confirma a exclusao deste cliente?
            </p>
            <div class="flex flex-col-reverse gap-2 border-t border-gray-100 pt-4 sm:flex-row sm:items-center sm:justify-end">
                <button type="button" id="delete-patient-no" class="rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-theme-xs hover:bg-gray-50">
                    Nao
                </button>
                <button type="button" id="delete-patient-yes" class="rounded-lg bg-error-500 px-4 py-2 text-sm font-semibold text-white shadow-theme-xs hover:bg-error-600">
                    Sim
                </button>
            </div>
        </div>
    </dialog>

    <script>
        document.querySelectorAll('[data-copy-booking-link]').forEach((button) => {
            button.addEventListener('click', async () => {
                const input = document.getElementById(button.dataset.target);
                if (! input) return;

                try {
                    await navigator.clipboard.writeText(input.value);
                    button.textContent = 'Copiado';
                } catch (_) {
                    input.focus();
                    input.select();
                    document.execCommand('copy');
                    button.textContent = 'Copiado';
                }

                setTimeout(() => {
                    button.textContent = 'Copiar';
                }, 1800);
            });
        });

        document.addEventListener('DOMContentLoaded', () => {
            const dialog = document.getElementById('delete-patient-dialog');
            const message = document.getElementById('delete-patient-message');
            const noButton = document.getElementById('delete-patient-no');
            const yesButton = document.getElementById('delete-patient-yes');
            let pendingForm = null;

            const closeDialog = () => {
                if (dialog?.open) dialog.close();
                pendingForm = null;
            };

            document.querySelectorAll('[data-delete-patient-form]').forEach((form) => {
                form.addEventListener('submit', (event) => {
                    event.preventDefault();
                    pendingForm = form;

                    if (message) {
                        message.textContent = form.dataset.hasAppointments === '1'
                            ? 'Este cliente possui agendamentos. Ao confirmar, ele sera removido da lista de clientes, mas o historico de agendamentos sera preservado. Deseja continuar?'
                            : 'Confirma a exclusao deste cliente?';
                    }

                    dialog?.showModal();
                    noButton?.focus();
                });
            });

            noButton?.addEventListener('click', closeDialog);

            yesButton?.addEventListener('click', () => {
                if (pendingForm) {
                    pendingForm.submit();
                }

                closeDialog();
            });

            dialog?.addEventListener('click', (event) => {
                if (event.target === dialog) {
                    closeDialog();
                }
            });
        });
    </script>
</x-app-layout>
