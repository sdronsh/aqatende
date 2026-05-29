<x-app-layout>
    <x-slot name="header">
        @php
            $selectedClinicName = $selectedClinicId ? ($clinics->firstWhere('id', $selectedClinicId)?->name) : null;
            if (! $selectedClinicName && $clinics->count() === 1) {
                $selectedClinicName = $clinics->first()->name;
            }
            $dashboardTitle = $selectedClinicName ? $selectedClinicName : 'Hoje';
        @endphp
        <div class="flex items-center justify-between gap-3">
            <div class="min-w-0">
                <h2 class="truncate text-lg font-semibold text-gray-900">{{ $dashboardTitle }}</h2>
                <p class="text-xs text-gray-500">{{ now()->translatedFormat('l, d \\d\\e F \\d\\e Y') }}</p>
            </div>
            <a class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-gray-200 bg-white text-brand-600 shadow-theme-xs hover:bg-brand-50 lg:hidden" href="{{ route('search') }}" aria-label="Buscar">
                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                    <path d="M9 15.5A6.5 6.5 0 1 0 9 2.5a6.5 6.5 0 0 0 0 13Z" stroke="currentColor" stroke-width="1.8"/>
                    <path d="m14 14 3.5 3.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                </svg>
            </a>
        </div>
    </x-slot>

    @php
        $formatMoney = fn ($cents) => 'R$ ' . number_format(($cents ?? 0) / 100, 2, ',', '.');
        $formatPatientPhone = function (?string $value): string {
            $digits = preg_replace('/\D+/', '', (string) $value) ?: '';
            if (str_starts_with($digits, '55') && strlen($digits) > 11) {
                $digits = substr($digits, 2);
            }

            if (strlen($digits) === 11) {
                return sprintf('(%s) %s-%s', substr($digits, 0, 2), substr($digits, 2, 5), substr($digits, 7, 4));
            }

            if (strlen($digits) === 10) {
                return sprintf('(%s) %s-%s', substr($digits, 0, 2), substr($digits, 2, 4), substr($digits, 6, 4));
            }

            return trim((string) $value);
        };
        $patientName = fn ($patient) => trim((string) ($patient?->full_name ?? '')) ?: 'Cliente';
        $patientPhone = fn ($patient) => $formatPatientPhone(($patient?->cellphone ?? '') ?: ($patient?->phone ?? ''));
        $companyId = session('active_company_id');
        $user = auth()->user();
        $can = fn ($permission) => $user?->is_platform_admin || ($companyId && $user?->hasCompanyPermission($companyId, $permission));
        $today = now();
        $statusMap = [
            'scheduled' => ['Agendado', 'border-warning-200 bg-warning-50 text-warning-800'],
            'agendado' => ['Agendado', 'border-warning-200 bg-warning-50 text-warning-800'],
            'confirmed' => ['Confirmado', 'border-success-200 bg-success-50 text-success-800'],
            'confirmado' => ['Confirmado', 'border-success-200 bg-success-50 text-success-800'],
            'waiting' => ['Na fila', 'border-brand-200 bg-brand-50 text-brand-800'],
            'in_progress' => ['Em atendimento', 'border-brand-200 bg-brand-50 text-brand-800'],
            'attended' => ['Atendido', 'border-gray-200 bg-gray-50 text-gray-700'],
            'atendido' => ['Atendido', 'border-gray-200 bg-gray-50 text-gray-700'],
            'done' => ['Finalizado', 'border-gray-200 bg-gray-50 text-gray-700'],
            'concluido' => ['Finalizado', 'border-gray-200 bg-gray-50 text-gray-700'],
            'cancelled' => ['Cancelado', 'border-error-200 bg-error-50 text-error-700'],
            'cancelado' => ['Cancelado', 'border-error-200 bg-error-50 text-error-700'],
        ];
        $quickActions = [
            [
                'label' => 'Agenda',
                'href' => route('agenda.index', ['view' => 'day', 'date' => $today->toDateString()]),
                'show' => $can('agendamento.agenda.view'),
                'icon' => 'calendar',
            ],
            [
                'label' => 'Fila',
                'href' => route('queue.index'),
                'show' => $can('atendimento.atendimentos.view'),
                'icon' => 'queue',
            ],
            [
                'label' => 'Agendar',
                'href' => route('appointments.create'),
                'show' => $can('agendamento.agendamentos.create'),
                'icon' => 'plus',
            ],
            [
                'label' => 'Clientes',
                'href' => route('patients.index'),
                'show' => $can('cadastro.pacientes.view'),
                'icon' => 'users',
            ],
            [
                'label' => 'Busca',
                'href' => route('search'),
                'show' => true,
                'icon' => 'search',
            ],
        ];
    @endphp

    <div class="space-y-5 pb-24">
        <section class="rounded-xl border border-gray-200 bg-white shadow-theme-sm">
            <div class="flex items-center justify-between border-b border-gray-100 px-3 py-3">
                @foreach ($quickActions as $action)
                    @continue(! $action['show'])
                    <a class="group flex min-w-0 flex-1 flex-col items-center gap-1 rounded-lg px-1 py-2 text-brand-600 hover:bg-brand-50" href="{{ $action['href'] }}">
                        <span class="flex h-8 w-8 items-center justify-center">
                            @if ($action['icon'] === 'calendar')
                                <svg class="h-6 w-6" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M5 2.5v3M15 2.5v3M3.25 8h13.5M4 4.5h12A1.5 1.5 0 0 1 17.5 6v10A1.5 1.5 0 0 1 16 17.5H4A1.5 1.5 0 0 1 2.5 16V6A1.5 1.5 0 0 1 4 4.5Z" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
                            @elseif ($action['icon'] === 'queue')
                                <svg class="h-6 w-6" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M3 5.5h10M3 10h14M3 14.5h8" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path d="m14.5 3 2.5 2.5L14.5 8" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            @elseif ($action['icon'] === 'plus')
                                <svg class="h-6 w-6" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M10 4v12M4 10h12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                            @elseif ($action['icon'] === 'users')
                                <svg class="h-6 w-6" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M7.5 10a3 3 0 1 0 0-6 3 3 0 0 0 0 6ZM2.5 17a5 5 0 0 1 10 0M14 9.5a2.5 2.5 0 0 0 0-5M13.5 12.5A4.5 4.5 0 0 1 17.5 17" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
                            @else
                                <svg class="h-6 w-6" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M9 15.5A6.5 6.5 0 1 0 9 2.5a6.5 6.5 0 0 0 0 13Z" stroke="currentColor" stroke-width="1.8"/><path d="m14 14 3.5 3.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                            @endif
                        </span>
                        <span class="truncate text-[11px] font-semibold text-gray-600 group-hover:text-brand-700">{{ $action['label'] }}</span>
                    </a>
                @endforeach
            </div>

            <div class="px-3 py-3">
                <div class="mb-1 text-center text-xs font-medium text-gray-400">{{ ucfirst($today->translatedFormat('F')) }}</div>
                <div class="gap-1" style="display: grid; grid-template-columns: repeat(7, minmax(0, 1fr));">
                    @foreach ($weekDays as $day)
                        @php
                            $isToday = $day->isSameDay($today);
                            $dayUrl = $can('agendamento.agenda.view')
                                ? route('agenda.index', ['view' => 'day', 'date' => $day->toDateString()])
                                : route('dashboard', ['period' => 'day']);
                        @endphp
                        <a class="rounded-lg px-1 py-2 text-center {{ $isToday ? 'bg-brand-600 text-white shadow-theme-xs' : 'text-gray-700 hover:bg-gray-50' }}" href="{{ $dayUrl }}">
                            <div class="text-[11px] font-medium {{ $isToday ? 'text-white/80' : 'text-gray-400' }}">{{ ucfirst($day->translatedFormat('D')) }}</div>
                            <div class="mt-1 text-lg font-semibold leading-none">{{ $day->format('d') }}</div>
                        </a>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-theme-sm">
                <div class="text-xs font-medium text-gray-500">Agenda de hoje</div>
                <div class="mt-2 text-2xl font-semibold text-gray-900">{{ $todayAppointments->count() }}</div>
                <div class="mt-1 text-xs text-gray-500">{{ $statusToday['confirmadas'] }} confirmados</div>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-theme-sm">
                <div class="text-xs font-medium text-gray-500">Fila</div>
                <div class="mt-2 text-2xl font-semibold text-gray-900">{{ $waitingCount }}</div>
                <div class="mt-1 text-xs text-gray-500">{{ $inProgressCount }} em atendimento</div>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-theme-sm">
                <div class="text-xs font-medium text-gray-500">Recebido no periodo</div>
                <div class="mt-2 text-2xl font-semibold text-gray-900">{{ $formatMoney($receitaCents) }}</div>
                <div class="mt-1 text-xs text-gray-500">{{ $rangeLabel }}</div>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-theme-sm">
                <div class="text-xs font-medium text-gray-500">Ocupacao</div>
                <div class="mt-2 text-2xl font-semibold text-gray-900">{{ $occupancyPercent }}%</div>
                <div class="mt-1 text-xs text-gray-500">{{ $occupiedHours }}h ocupadas</div>
            </div>
        </section>

        <section class="rounded-xl border border-gray-200 bg-white p-3 shadow-theme-sm">
            <form method="GET" class="grid gap-2 md:grid-cols-[repeat(3,minmax(0,1fr))_auto]">
                <input type="hidden" name="period" value="day" />
                <select class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 shadow-theme-xs focus:border-brand-300 focus:outline-none focus:ring-3 focus:ring-brand-500/10" name="clinic_id">
                    <option value="">Todas as empresas</option>
                    @foreach ($clinics as $clinic)
                        <option value="{{ $clinic->id }}" @selected($selectedClinicId === $clinic->id)>{{ $clinic->name }}</option>
                    @endforeach
                </select>
                <select class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 shadow-theme-xs focus:border-brand-300 focus:outline-none focus:ring-3 focus:ring-brand-500/10" name="unit_id">
                    <option value="">Todas as unidades</option>
                    @foreach ($units as $unit)
                        <option value="{{ $unit->id }}" @selected($selectedUnitId === $unit->id)>{{ $unit->name }}</option>
                    @endforeach
                </select>
                <select class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 shadow-theme-xs focus:border-brand-300 focus:outline-none focus:ring-3 focus:ring-brand-500/10" name="professional_id">
                    <option value="">Todos os profissionais</option>
                    @foreach ($professionals as $professional)
                        <option value="{{ $professional->id }}" @selected($selectedProfessionalId === $professional->id)>{{ $professional->display_name }}</option>
                    @endforeach
                </select>
                <button class="rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-theme-xs hover:bg-gray-50" type="submit">Aplicar</button>
            </form>
        </section>

        <section class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_360px]">
            <div class="rounded-xl border border-gray-200 bg-white shadow-theme-sm">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 px-4 py-3">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-900">Agenda do dia</h3>
                        <p class="text-xs text-gray-500">Agendamentos marcados para hoje</p>
                    </div>
                    <div class="inline-flex rounded-lg border border-gray-200 bg-white p-1 text-xs font-semibold">
                        <a class="rounded-md bg-brand-50 px-3 py-1.5 text-brand-700" href="{{ route('dashboard', ['period' => 'day']) }}">Agenda</a>
                        @if ($can('atendimento.atendimentos.view'))
                            <a class="rounded-md px-3 py-1.5 text-gray-600 hover:bg-gray-50" href="{{ route('queue.index') }}">Fila</a>
                        @endif
                        @if ($can('agendamento.agendamentos.view'))
                            <a class="rounded-md px-3 py-1.5 text-gray-600 hover:bg-gray-50" href="{{ route('appointments.index') }}">Agendamentos</a>
                        @endif
                    </div>
                </div>

                <div class="divide-y divide-gray-100">
                    @forelse ($todayAppointments as $appointment)
                        @php
                            $rawStatus = strtolower($appointment->status ?? 'scheduled');
                            [$statusLabel, $statusClass] = $statusMap[$rawStatus] ?? [ucfirst($appointment->status ?? 'Agendado'), 'border-gray-200 bg-gray-50 text-gray-700'];
                            if ($can('agendamento.agendamentos.update')) {
                                $appointmentUrl = route('appointments.edit', $appointment);
                            } elseif ($can('agendamento.agendamentos.view')) {
                                $appointmentUrl = route('appointments.index');
                            } elseif ($can('agendamento.agenda.view')) {
                                $appointmentUrl = route('agenda.index', ['view' => 'day', 'date' => $today->toDateString()]);
                            } else {
                                $appointmentUrl = route('dashboard', ['period' => 'day']);
                            }
                        @endphp
                        <div class="grid gap-3 px-4 py-4 md:grid-cols-[80px_minmax(0,1fr)_auto] md:items-center">
                            <div class="flex items-center gap-3 md:block">
                                <div class="text-xl font-semibold text-gray-900 md:text-2xl">{{ $appointment->scheduled_at?->format('H:i') }}</div>
                                @if ($appointment->ends_at)
                                    <div class="text-xs text-gray-400">ate {{ $appointment->ends_at->format('H:i') }}</div>
                                @endif
                            </div>
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-start gap-2">
                                    <a class="min-w-0 max-w-full text-sm font-semibold text-gray-900 hover:text-brand-700" href="{{ $appointmentUrl }}">
                                        <span class="block truncate">{{ $patientName($appointment->patient) }}</span>
                                        @if ($patientPhone($appointment->patient))
                                            <span class="mt-0.5 block truncate text-xs font-medium text-gray-500">{{ $patientPhone($appointment->patient) }}</span>
                                        @endif
                                    </a>
                                    <span class="rounded-full border px-2 py-0.5 text-[11px] font-semibold {{ $statusClass }}">{{ $statusLabel }}</span>
                                </div>
                                <div class="mt-1 truncate text-sm text-gray-600">{{ $appointment->serviceNames() }}</div>
                                <div class="mt-1 flex flex-wrap gap-x-3 gap-y-1 text-xs text-gray-400">
                                    <span>{{ $appointment->professional?->display_name ?? 'Profissional nao definido' }}</span>
                                    @if ($appointment->unit)
                                        <span>{{ $appointment->unit->name }}</span>
                                    @endif
                                </div>
                            </div>
                            <div class="flex flex-wrap gap-2 md:justify-end">
                                @if ($can('agendamento.agendamentos.update'))
                                    <a class="inline-flex rounded-lg bg-brand-600 px-3 py-2 text-xs font-semibold text-white shadow-theme-xs hover:bg-brand-700" href="{{ route('appointments.edit', $appointment) }}">
                                        Finalizar
                                    </a>
                                @endif
                                <a class="inline-flex rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs font-semibold text-gray-700 shadow-theme-xs hover:bg-gray-50" href="{{ $appointmentUrl }}">
                                    Abrir
                                </a>
                            </div>
                        </div>
                    @empty
                        <div class="px-4 py-12 text-center">
                            <div class="text-sm font-semibold text-gray-800">Nenhum agendamento para hoje</div>
                            <p class="mt-1 text-sm text-gray-500">Use o botao de novo agendamento ou acompanhe a fila.</p>
                            @if ($can('agendamento.agendamentos.create'))
                                <a class="mt-4 inline-flex rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-theme-xs hover:bg-brand-700" href="{{ route('appointments.create') }}">Novo agendamento</a>
                            @endif
                        </div>
                    @endforelse
                </div>
            </div>

            <aside class="space-y-5">
                <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-theme-sm">
                    <div class="flex items-center justify-between">
                        <h3 class="text-sm font-semibold text-gray-900">Status de hoje</h3>
                        <a class="text-xs font-semibold text-brand-700 hover:text-brand-800" href="{{ route('agenda.index', ['view' => 'day', 'date' => $today->toDateString()]) }}">Ver agenda</a>
                    </div>
                    <div class="mt-4 grid gap-2">
                        <div class="flex items-center justify-between rounded-lg bg-gray-50 px-3 py-2 text-sm"><span class="text-gray-600">Confirmadas</span><span class="font-semibold text-gray-900">{{ $statusToday['confirmadas'] }}</span></div>
                        <div class="flex items-center justify-between rounded-lg bg-gray-50 px-3 py-2 text-sm"><span class="text-gray-600">Atendidas</span><span class="font-semibold text-gray-900">{{ $statusToday['atendidas'] }}</span></div>
                        <div class="flex items-center justify-between rounded-lg bg-gray-50 px-3 py-2 text-sm"><span class="text-gray-600">Canceladas</span><span class="font-semibold text-gray-900">{{ $statusToday['canceladas'] }}</span></div>
                        <div class="flex items-center justify-between rounded-lg bg-gray-50 px-3 py-2 text-sm"><span class="text-gray-600">Faltas</span><span class="font-semibold text-gray-900">{{ $statusToday['faltas'] }}</span></div>
                    </div>
                </div>

                <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-theme-sm">
                    <h3 class="text-sm font-semibold text-gray-900">Profissionais no periodo</h3>
                    <div class="mt-4 grid gap-3">
                        @forelse ($professionalStats as $stat)
                            <div class="space-y-1">
                                <div class="flex items-center justify-between text-xs">
                                    <span class="truncate text-gray-600">{{ $stat['name'] }}</span>
                                    <span class="font-semibold text-gray-800">{{ $stat['total'] }}</span>
                                </div>
                                <div class="h-2 overflow-hidden rounded-full bg-gray-100">
                                    <div class="h-full rounded-full bg-brand-600" style="width: {{ $stat['percent'] }}%;"></div>
                                </div>
                            </div>
                        @empty
                            <div class="rounded-lg bg-gray-50 px-3 py-4 text-sm text-gray-500">Sem movimento no periodo.</div>
                        @endforelse
                    </div>
                </div>
            </aside>
        </section>
    </div>

    @if ($can('agendamento.agendamentos.create') || $can('atendimento.atendimentos.view') || $can('cadastro.pacientes.create'))
        <details class="group fixed bottom-5 right-5 z-99999">
            <summary class="flex h-16 w-16 list-none items-center justify-center rounded-full bg-brand-600 text-4xl leading-none text-white shadow-theme-xl hover:bg-brand-700 [&::-webkit-details-marker]:hidden">
                +
            </summary>
            <div class="absolute bottom-20 right-0 grid w-52 gap-2 rounded-xl border border-gray-200 bg-white p-2 shadow-theme-lg">
                @if ($can('agendamento.agendamentos.create'))
                    <a class="rounded-lg px-3 py-2 text-sm font-semibold text-gray-700 hover:bg-brand-50 hover:text-brand-700" href="{{ route('appointments.create') }}">Novo agendamento</a>
                @endif
                @if ($can('atendimento.atendimentos.view'))
                    <a class="rounded-lg px-3 py-2 text-sm font-semibold text-gray-700 hover:bg-brand-50 hover:text-brand-700" href="{{ route('queue.index') }}">Abrir fila</a>
                @endif
                @if ($can('cadastro.pacientes.create'))
                    <a class="rounded-lg px-3 py-2 text-sm font-semibold text-gray-700 hover:bg-brand-50 hover:text-brand-700" href="{{ route('patients.create') }}">Novo cliente</a>
                @endif
            </div>
        </details>
    @endif
</x-app-layout>
