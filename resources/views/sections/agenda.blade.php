@php
    $pageTitle = $pageTitle ?? 'Agenda';
    $lockProfessionalFilter = $lockProfessionalFilter ?? false;
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-gray-800">{{ $pageTitle }}</h2>
    </x-slot>

    <div class="space-y-4">
        <div class="rounded-xl border border-gray-200 bg-white p-3 shadow-theme-sm md:sticky md:top-4 md:z-10">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="flex min-w-0 items-center gap-2">
                    <a class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50" href="{{ request()->fullUrlWithQuery(['date' => $prevDate->toDateString()]) }}">
                        <span class="text-lg">&lsaquo;</span>
                    </a>
                    <div class="min-w-0">
                        <div class="text-xs uppercase text-gray-400">{{ ucfirst($viewMode) }}</div>
                        <div class="truncate text-sm font-semibold text-gray-800">
                            @if ($viewMode === 'month')
                                {{ $date->translatedFormat('F Y') }}
                            @elseif ($viewMode === 'week')
                                Semana de {{ $date->copy()->startOfWeek(\Illuminate\Support\Carbon::MONDAY)->format('d/m') }}
                            @else
                                {{ $date->translatedFormat('d \\d\\e F Y') }}
                            @endif
                        </div>
                    </div>
                    <a class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50" href="{{ request()->fullUrlWithQuery(['date' => $nextDate->toDateString()]) }}">
                        <span class="text-lg">&rsaquo;</span>
                    </a>
                </div>

                <div class="flex w-full flex-wrap items-center gap-2 lg:w-auto">
                    @php
                        $dayUrl = request()->fullUrlWithQuery(['view' => 'day']);
                        $weekUrl = request()->fullUrlWithQuery(['view' => 'week']);
                        $monthUrl = request()->fullUrlWithQuery(['view' => 'month']);
                    @endphp
                    <div class="inline-flex rounded-lg border border-gray-200 bg-white p-1 text-sm">
                        <a class="inline-flex rounded-md px-3 py-1.5 max-md:hidden {{ $viewMode === 'day' ? 'bg-brand-50 text-brand-600' : 'text-gray-600 hover:bg-gray-50' }}" href="{{ $dayUrl }}">Diario</a>
                        <a class="rounded-md px-3 py-1.5 {{ $viewMode === 'week' ? 'bg-brand-50 text-brand-600' : 'text-gray-600 hover:bg-gray-50' }}" href="{{ $weekUrl }}">Semanal</a>
                        <a class="inline-flex rounded-md px-3 py-1.5 max-md:hidden {{ $viewMode === 'month' ? 'bg-brand-50 text-brand-600' : 'text-gray-600 hover:bg-gray-50' }}" href="{{ $monthUrl }}">Mensal</a>
                    </div>
                    <form method="GET" class="grid w-full gap-2 sm:flex sm:w-auto sm:flex-wrap sm:items-center">
                        <input type="hidden" name="view" value="{{ $viewMode }}" />
                        <input class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 shadow-theme-xs focus:border-brand-300 focus:outline-none focus:ring-3 focus:ring-brand-500/10 sm:w-auto" type="date" name="date" value="{{ $date->toDateString() }}" />
                        @if ($lockProfessionalFilter)
                            <input type="hidden" name="professional_id" value="{{ $selectedProfessionalId }}" />
                        @endif
                        <select class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 shadow-theme-xs focus:border-brand-300 focus:outline-none focus:ring-3 focus:ring-brand-500/10 sm:w-auto {{ $lockProfessionalFilter ? 'bg-gray-100 text-gray-500' : '' }}" name="professional_id" @disabled($lockProfessionalFilter)>
                            <option value="">Todos os profissionais</option>
                            @foreach (($filterProfessionals ?? $professionals) as $professional)
                                <option value="{{ $professional->id }}" @selected($selectedProfessionalId === $professional->id)>
                                    {{ $professional->display_name }}
                                </option>
                            @endforeach
                        </select>
                        <select class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 shadow-theme-xs focus:border-brand-300 focus:outline-none focus:ring-3 focus:ring-brand-500/10 sm:w-auto" name="unit_id">
                            <option value="">Todas as unidades</option>
                            @foreach ($units as $unit)
                                <option value="{{ $unit->id }}" @selected($selectedUnitId === $unit->id)>
                                    {{ $unit->name }}
                                </option>
                            @endforeach
                        </select>
                        <button class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-theme-xs hover:bg-gray-50 sm:w-auto" type="submit">Aplicar</button>
                    </form>
                    @php
                        $canCreateAppointment = auth()->user()->is_platform_admin
                            || auth()->user()->hasCompanyPermission(session('active_company_id'), 'agendamento.agendamentos.create');
                    @endphp
                    @if ($canCreateAppointment)
                        <button class="w-full rounded-lg bg-brand-500 px-4 py-2 text-sm font-semibold text-white shadow-theme-xs hover:bg-brand-600 sm:w-auto" type="button" data-open-modal="appointment-modal">
                            + Agendar
                        </button>
                    @endif
                </div>
            </div>
            @if (request()->boolean('debug'))
                <div class="mt-3 rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-xs text-gray-600">
                    Horario ativo: {{ $scheduleStart }} - {{ $scheduleEnd }} | Agendamentos dia: {{ $appointmentsCount }} | Eventos renderizados: {{ $eventsCount }}
                    @if (! empty($debugInfo))
                        <div class="mt-2 grid gap-1 text-[11px] text-gray-500">
                            <div>company_id: {{ $debugInfo['company_id'] ?? '-' }}</div>
                            <div>user_id: {{ $debugInfo['user_id'] ?? '-' }}</div>
                            <div>user_is_patient: {{ $debugInfo['user_is_patient'] ? 'sim' : 'nao' }}</div>
                            <div>user_is_professional: {{ $debugInfo['user_is_professional'] ? 'sim' : 'nao' }}</div>
                            <div>selected_clinic_id: automatico pela empresa</div>
                            <div>selected_unit_id: {{ $debugInfo['selected_unit_id'] ?? '-' }}</div>
                            <div>selected_professional_id: {{ $debugInfo['selected_professional_id'] ?? '-' }}</div>
                            <div>clinic_ids: {{ implode(',', $debugInfo['clinic_ids'] ?? []) }}</div>
                            <div>range: {{ $debugInfo['range_start'] ?? '-' }} -> {{ $debugInfo['range_end'] ?? '-' }}</div>
                            <div>appointment_ids: {{ implode(',', $debugInfo['appointment_ids'] ?? []) }}</div>
                        </div>
                    @endif
                </div>
            @endif
        </div>

        @if ($viewMode === 'day')
            <div class="overflow-x-auto rounded-xl border border-gray-200 bg-white shadow-theme-sm">
                <div class="min-w-[900px]">
                        <div class="flex border-b border-gray-200 bg-gray-50">
                            <div class="w-56 px-4 py-3 text-xs font-semibold uppercase text-gray-500">Profissionais</div>
                            <div class="flex-1">
                                <div class="grid" style="grid-template-columns: repeat({{ count($timeSlots) }}, minmax(0, 1fr));">
                                    @foreach ($timeSlots as $slot)
                                        <div class="border-l border-gray-200 px-1 py-2 text-xs text-gray-400">
                                            @if ($slot['is_hour'])
                                                {{ $slot['label'] }}
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        @forelse ($professionals as $professional)
                            @php
                                $nameParts = preg_split('/\s+/', trim($professional->display_name));
                                $initials = strtoupper(substr($nameParts[0] ?? 'P', 0, 1) . substr($nameParts[count($nameParts) - 1] ?? '', 0, 1));
                                $events = $eventsByProfessional[$professional->id] ?? [];
                            @endphp
                            @php
                                $rowHeight = $rowHeights[$professional->id] ?? 260;
                                $laneHeight = $laneHeight ?? 88;
                            @endphp
                            <div class="flex border-b border-gray-100 last:border-b-0" style="min-height: {{ $rowHeight }}px;">
                                <div class="w-56 px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <div class="h-9 w-9 rounded-full bg-gray-100 text-xs font-semibold text-gray-700 flex items-center justify-center">
                                            {{ $initials }}
                                        </div>
                                        <div>
                                            <div class="text-sm font-medium text-gray-800">{{ $professional->display_name }}</div>
                                            <div class="text-xs text-gray-400">{{ $professional->crm_state }} {{ $professional->crm_number }}</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="relative flex-1" style="min-height: {{ $rowHeight }}px;">
                                    <div class="grid h-full" style="grid-template-columns: repeat({{ count($timeSlots) }}, minmax(0, 1fr));">
                                        @foreach ($timeSlots as $slot)
                                            <div class="border-l border-gray-100"></div>
                                        @endforeach
                                    </div>
                                    @foreach ($events as $event)
                                        @php
                                            $eventClass = $event['type'] === 'block'
                                                ? 'bg-error-50 border-error-200 text-error-800'
                                                : ($event['status_class'] ?? 'bg-warning-50 border-warning-200 text-warning-900');
                                            $laneTop = 12 + (($event['lane'] ?? 0) * $laneHeight);
                                        @endphp
                                        @if (! empty($event['edit_url']))
                                            <a class="absolute h-24 overflow-hidden rounded-lg border px-2 py-1 text-xs leading-tight shadow-theme-xs {{ $eventClass }} hover:ring-2 hover:ring-brand-500/30" style="left: {{ $event['left'] }}%; width: {{ $event['width'] }}%; top: {{ $laneTop }}px;" href="{{ $event['edit_url'] }}" title="Abrir agendamento">
                                                <div class="truncate font-semibold">{{ $event['title'] }}</div>
                                                @if (! empty($event['subtitle']))
                                                    <div class="truncate text-[11px]">{{ $event['subtitle'] }}</div>
                                                @endif
                                                @if (! empty($event['professional']))
                                                    <div class="truncate text-[11px] text-gray-600">{{ $event['professional'] }}</div>
                                                @endif
                                                <div class="truncate text-[11px] opacity-70">{{ $event['time'] }}</div>
                                            </a>
                                        @else
                                            <div class="absolute h-24 overflow-hidden rounded-lg border px-2 py-1 text-xs leading-tight shadow-theme-xs {{ $eventClass }}" style="left: {{ $event['left'] }}%; width: {{ $event['width'] }}%; top: {{ $laneTop }}px;">
                                                <div class="truncate font-semibold">{{ $event['title'] }}</div>
                                                @if (! empty($event['subtitle']))
                                                    <div class="truncate text-[11px]">{{ $event['subtitle'] }}</div>
                                                @endif
                                                @if (! empty($event['professional']))
                                                    <div class="truncate text-[11px] text-gray-600">{{ $event['professional'] }}</div>
                                                @endif
                                                <div class="truncate text-[11px] opacity-70">{{ $event['time'] }}</div>
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        @empty
                            <div class="px-6 py-10 text-center text-sm text-gray-500">Nenhum profissional encontrado.</div>
                        @endforelse
                </div>
            </div>
        @elseif ($viewMode === 'week')
            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-theme-sm md:p-6">
                @php
                    $isAttendanceView = ($pageTitle ?? '') === 'Atendimento';
                @endphp
                <div class="grid gap-3 md:grid-cols-7">
                    @foreach ($weekDays as $day)
                        @php
                            $dayKey = $day->toDateString();
                            $dayAppointments = $appointmentsByDay->get($dayKey, collect());
                        @endphp
                        <div class="rounded-lg border border-gray-200 bg-white p-3">
                            <div class="text-xs uppercase text-gray-400">{{ $day->translatedFormat('D') }}</div>
                            <div class="text-sm font-semibold text-gray-800">{{ $day->format('d/m') }}</div>
                            <div class="mt-2 space-y-2">
                                @forelse ($dayAppointments as $appointment)
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
                                        $isCancelled = $status === 'cancelado';
                                        $isConfirmed = in_array($status, ['confirmado', 'atendido', 'concluido'], true);
                                        if ($isCancelled) {
                                            $chipClass = 'bg-error-50 text-error-800 border-error-200';
                                        } elseif ($isConfirmed) {
                                            $chipClass = 'bg-success-50 text-success-800 border-success-200';
                                        } elseif (in_array($appointment->channel, ['home_care', 'whatsapp', 'teleconsulta'], true)) {
                                            $chipClass = 'bg-brand-50 text-brand-800 border-brand-200';
                                        } else {
                                            $chipClass = 'bg-warning-50 text-warning-900 border-warning-200';
                                        }
                                        $cardUrl = $isAttendanceView
                                            ? route('attendance.record.edit', $appointment)
                                            : route('appointments.edit', $appointment);
                                    @endphp
                                    <a class="block rounded-md border px-2 py-1 text-xs {{ $chipClass }} hover:ring-2 hover:ring-brand-500/30" href="{{ $cardUrl }}" title="Abrir {{ $isAttendanceView ? 'atendimento' : 'agendamento' }}">
                                        <div class="font-semibold">{{ $appointment->scheduled_at->format('H:i') }}</div>
                                        <div class="truncate">{{ $appointment->patient?->full_name ?? 'Cliente' }}</div>
                                        <div class="truncate text-[11px] text-gray-500">{{ $appointment->professional?->display_name ?? 'Profissional' }}</div>
                                        <div class="truncate text-[11px] text-gray-500">{{ $appointment->serviceNames() }}</div>
                                    </a>
                                @empty
                                    <div class="text-xs text-gray-400">Sem agendamentos</div>
                                @endforelse
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @else
            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-theme-sm md:p-6">
                @php
                    $isAttendanceView = ($pageTitle ?? '') === 'Atendimento';
                @endphp
                <div class="grid gap-4 lg:grid-cols-[240px_minmax(0,1fr)]">
                    @php
                        $weekLabels = ['Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sab', 'Dom'];
                        $selectedDay = $date->toDateString();
                    @endphp
                    <div class="space-y-4">
                        <div class="rounded-lg border border-gray-200 bg-white p-4">
                            <div class="text-sm font-semibold text-gray-800">{{ $date->translatedFormat('F Y') }}</div>
                            <div class="mt-3 grid gap-1 text-[11px] text-gray-400" style="grid-template-columns: repeat(7, minmax(0, 1fr));">
                                @foreach ($weekLabels as $label)
                                    <div class="text-center font-semibold uppercase">{{ $label }}</div>
                                @endforeach
                                @foreach ($calendarDays as $day)
                                    @php
                                        $miniInMonth = $day->month === $date->month;
                                        $miniUrl = request()->fullUrlWithQuery(['date' => $day->toDateString(), 'view' => 'month']);
                                        $miniClass = $day->toDateString() === $selectedDay
                                            ? 'bg-brand-500 text-white'
                                            : ($day->isToday() ? 'bg-brand-50 text-brand-600' : 'text-gray-600');
                                    @endphp
                                    <a class="flex h-7 items-center justify-center rounded {{ $miniInMonth ? $miniClass : 'text-gray-300' }}" href="{{ $miniUrl }}">
                                        {{ $day->day }}
                                    </a>
                                @endforeach
                            </div>
                        </div>
                        <div class="rounded-lg border border-gray-200 bg-white p-4 text-xs text-gray-500">
                            <div class="font-semibold text-gray-700">Legendas</div>
                            <div class="mt-2 flex flex-col gap-2">
                                <div class="flex items-center gap-2">
                                    <span class="h-2 w-2 rounded-full bg-warning-400"></span>
                                    Atendimento presencial
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="h-2 w-2 rounded-full bg-brand-500"></span>
                                    Home Care
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <div class="grid min-w-[720px] gap-px overflow-hidden rounded-lg border border-gray-200 bg-gray-200 text-xs sm:min-w-[960px]" style="grid-template-columns: repeat(7, minmax(0, 1fr));">
                        @foreach ($weekLabels as $label)
                            <div class="bg-gray-50 px-3 py-2 text-[11px] font-semibold uppercase text-gray-500">{{ $label }}</div>
                        @endforeach
                        @foreach ($calendarDays as $day)
                            @php
                                $dayKey = $day->toDateString();
                                $dayAppointments = $appointmentsByDay->get($dayKey, collect());
                                $inMonth = $day->month === $date->month;
                                $maxVisible = 4;
                            @endphp
                            <div class="min-h-[140px] bg-white px-2 py-2 {{ $inMonth ? '' : 'text-gray-400 bg-gray-50' }}">
                                <div class="flex items-center justify-between">
                                    <div class="text-[11px] font-semibold {{ $day->isToday() ? 'rounded-full bg-brand-500 px-2 py-0.5 text-white' : '' }}">
                                        {{ $day->day }}
                                    </div>
                                </div>
                                <div class="mt-2 space-y-1">
                                    @foreach ($dayAppointments->take($maxVisible) as $appointment)
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
                                            $isCancelled = $status === 'cancelado';
                                            $isConfirmed = in_array($status, ['confirmado', 'atendido', 'concluido'], true);
                                            if ($isCancelled) {
                                                $chipClass = 'bg-error-50 text-error-800 border-error-200';
                                                $dotClass = 'bg-error-500';
                                            } elseif ($isConfirmed) {
                                                $chipClass = 'bg-success-50 text-success-800 border-success-200';
                                                $dotClass = 'bg-success-500';
                                            } elseif (in_array($appointment->channel, ['home_care', 'whatsapp', 'teleconsulta'], true)) {
                                                $chipClass = 'bg-brand-50 text-brand-800 border-brand-200';
                                                $dotClass = 'bg-brand-500';
                                            } else {
                                                $chipClass = 'bg-warning-50 text-warning-900 border-warning-200';
                                                $dotClass = 'bg-warning-500';
                                            }
                                            $cardUrl = $isAttendanceView
                                                ? route('attendance.record.edit', $appointment)
                                                : route('appointments.edit', $appointment);
                                        @endphp
                                        <a class="flex items-center gap-2 rounded border px-2 py-1 text-[11px] {{ $chipClass }} hover:ring-2 hover:ring-brand-500/30" href="{{ $cardUrl }}" title="Abrir {{ $isAttendanceView ? 'atendimento' : 'agendamento' }}">
                                            <span class="h-1.5 w-1.5 rounded-full {{ $dotClass }}"></span>
                                            <span class="font-semibold">{{ $appointment->scheduled_at->format('H:i') }}</span>
                                            <span class="truncate">{{ $appointment->patient?->full_name ?? 'Cliente' }}</span>
                                            <span class="truncate text-[10px] text-gray-500">{{ $appointment->professional?->display_name ?? 'Profissional' }}</span>
                                        </a>
                                    @endforeach
                                    @if ($dayAppointments->count() > $maxVisible)
                                        <div class="text-[11px] text-gray-500">+{{ $dayAppointments->count() - $maxVisible }} mais</div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <dialog id="appointment-modal" class="m-auto max-h-[90vh] w-[calc(100%-2rem)] max-w-2xl overflow-y-auto rounded-xl border border-gray-200 p-0 shadow-theme-lg">
        <form method="POST" action="{{ route('appointments.store') }}" class="flex flex-col gap-4 p-4 sm:p-5">
            @csrf
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-800">Novo agendamento</h3>
                <button type="button" class="text-gray-500 hover:text-gray-700" data-close-modal>&times;</button>
            </div>

            <div class="grid gap-3 md:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700" for="unit_id">Unidade</label>
                    <select class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 shadow-theme-xs focus:border-brand-300 focus:outline-none focus:ring-3 focus:ring-brand-500/10" id="unit_id" name="unit_id" required>
                        <option value="">Selecione</option>
                        @foreach ($units as $unit)
                            <option value="{{ $unit->id }}" @selected((string) old('unit_id', $selectedUnitId ?: ($units->count() === 1 ? $units->first()->id : '')) === (string) $unit->id)>{{ $unit->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700" for="patient_id">Cliente</label>
                    <select class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 shadow-theme-xs focus:border-brand-300 focus:outline-none focus:ring-3 focus:ring-brand-500/10" id="patient_id" name="patient_id" required>
                        <option value="">Selecione</option>
                        @foreach ($patients as $patient)
                            <option value="{{ $patient->id }}">{{ $patient->full_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Servicos</label>
                    <div class="max-h-56 overflow-y-auto rounded-lg border border-gray-200 bg-white shadow-theme-xs">
                        @foreach ($services as $service)
                            @php
                                $servicePrice = $service->price_cents !== null
                                    ? number_format($service->price_cents / 100, 2, ',', '.')
                                    : '';
                            @endphp
                            <div class="grid gap-2 border-b border-gray-100 px-3 py-2 text-sm last:border-b-0 hover:bg-gray-50 md:grid-cols-[1fr_220px]">
                            <label class="flex cursor-pointer items-center gap-3">
                                <input
                                    type="checkbox"
                                    name="service_ids[]"
                                    value="{{ $service->id }}"
                                    class="h-4 w-4 rounded border-gray-300 text-brand-500 focus:ring-brand-500"
                                    data-agenda-service-option
                                    data-price="{{ $servicePrice }}"
                                    data-duration="{{ $service->duration_minutes }}"
                                />
                                <span class="flex-1 text-gray-700">{{ $service->name }}</span>
                                <span class="text-xs text-gray-500">R$ {{ $servicePrice }}</span>
                            </label>
                            <select class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 shadow-theme-xs" name="service_professional_ids[{{ $service->id }}]">
                                <option value="">Selecione profissional</option>
                                @foreach (($appointmentProfessionals ?? $professionals) as $professional)
                                    @if ($professional->services->contains('id', $service->id))
                                        <option value="{{ $professional->id }}">{{ $professional->display_name }}</option>
                                    @endif
                                @endforeach
                            </select>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700" for="channel">Canal</label>
                    <select class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 shadow-theme-xs focus:border-brand-300 focus:outline-none focus:ring-3 focus:ring-brand-500/10" id="channel" name="channel" required>
                        <option value="presencial">Presencial</option>
                        <option value="home_care">Home Care</option>
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700" for="scheduled_at">Data e horario</label>
                    <input class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 shadow-theme-xs focus:border-brand-300 focus:outline-none focus:ring-3 focus:ring-brand-500/10" type="datetime-local" id="scheduled_at" name="scheduled_at" required />
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700" for="duration_minutes">Duracao (min)</label>
                    <input class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 shadow-theme-xs focus:border-brand-300 focus:outline-none focus:ring-3 focus:ring-brand-500/10" type="number" min="5" max="480" id="duration_minutes" name="duration_minutes" placeholder="Ex: 30" />
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700" for="price">Preco (R$)</label>
                    <input class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 shadow-theme-xs focus:border-brand-300 focus:outline-none focus:ring-3 focus:ring-brand-500/10" type="text" inputmode="decimal" id="price" name="price" placeholder="Ex: 80,00" />
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700" for="recurrence_type">Recorrencia</label>
                    <select class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 shadow-theme-xs focus:border-brand-300 focus:outline-none focus:ring-3 focus:ring-brand-500/10" id="recurrence_type" name="recurrence_type">
                        <option value="none" @selected(old('recurrence_type', 'none') === 'none')>Sem recorrencia</option>
                        <option value="days" @selected(old('recurrence_type') === 'days')>Por quantidade de dias</option>
                        <option value="weekly" @selected(old('recurrence_type') === 'weekly')>Semanal</option>
                        <option value="biweekly" @selected(old('recurrence_type') === 'biweekly')>Quinzenal</option>
                        <option value="monthly" @selected(old('recurrence_type') === 'monthly')>Mensal</option>
                        <option value="semiannual" @selected(old('recurrence_type') === 'semiannual')>Semestral</option>
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700" for="recurrence_occurrences">Quantidade de ocorrencias</label>
                    <input class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 shadow-theme-xs focus:border-brand-300 focus:outline-none focus:ring-3 focus:ring-brand-500/10" type="number" min="1" max="120" id="recurrence_occurrences" name="recurrence_occurrences" value="{{ old('recurrence_occurrences', 1) }}" />
                </div>
                <div id="recurrence-interval-days-wrapper" class="hidden">
                    <label class="mb-1 block text-sm font-medium text-gray-700" for="recurrence_interval_days">Intervalo em dias</label>
                    <input class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 shadow-theme-xs focus:border-brand-300 focus:outline-none focus:ring-3 focus:ring-brand-500/10" type="number" min="1" max="365" id="recurrence_interval_days" name="recurrence_interval_days" value="{{ old('recurrence_interval_days', 1) }}" />
                </div>
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700" for="notes">Observacoes</label>
                <textarea class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 shadow-theme-xs focus:border-brand-300 focus:outline-none focus:ring-3 focus:ring-brand-500/10" id="notes" name="notes" rows="3"></textarea>
            </div>

            <div class="flex items-center justify-end gap-2 border-t border-gray-100 pt-4">
                <button type="button" class="rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-theme-xs hover:bg-gray-50" data-close-modal>Cancelar</button>
                <button type="submit" class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-semibold text-white shadow-theme-xs hover:bg-brand-600">Salvar</button>
            </div>
        </form>
    </dialog>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const viewMode = @json($viewMode);
            if (window.matchMedia('(max-width: 767px)').matches && viewMode !== 'week') {
                const url = new URL(window.location.href);
                url.searchParams.set('view', 'week');
                window.location.replace(url.toString());
                return;
            }
            const dialog = document.getElementById('appointment-modal');
            if (!dialog) return;
            dialog.style.margin = 'auto';
            document.querySelectorAll('[data-open-modal="appointment-modal"]').forEach((button) => {
                button.addEventListener('click', () => dialog.showModal());
            });
            dialog.querySelectorAll('[data-close-modal]').forEach((button) => {
                button.addEventListener('click', () => dialog.close());
            });
            dialog.addEventListener('click', (event) => {
                if (event.target === dialog) {
                    dialog.close();
                }
            });

            const serviceOptions = Array.from(dialog.querySelectorAll('[data-agenda-service-option]'));
            const priceInput = dialog.querySelector('#price');
            const durationInput = dialog.querySelector('#duration_minutes');
            const moneyToNumber = (value) => Number((value || '0').replace(/\./g, '').replace(',', '.')) || 0;
            const numberToMoney = (value) => value.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            const applyServicePrice = () => {
                if (!serviceOptions.length || !priceInput) return;
                const selected = serviceOptions.filter((option) => option.checked);
                if (!selected.length) return;
                const price = selected.reduce((sum, option) => sum + moneyToNumber(option.dataset.price), 0);
                const duration = selected.reduce((sum, option) => sum + (Number(option.dataset.duration) || 0), 0);
                if (price > 0) {
                    priceInput.value = numberToMoney(price);
                }
                if (durationInput && duration > 0) {
                    durationInput.value = duration;
                }
            };
            serviceOptions.forEach((option) => option.addEventListener('change', applyServicePrice));

            const recurrenceType = dialog.querySelector('#recurrence_type');
            const recurrenceIntervalWrapper = dialog.querySelector('#recurrence-interval-days-wrapper');
            const updateRecurrenceFields = () => {
                if (!recurrenceType || !recurrenceIntervalWrapper) return;
                const showIntervalDays = recurrenceType.value === 'days';
                recurrenceIntervalWrapper.classList.toggle('hidden', !showIntervalDays);
            };
            recurrenceType?.addEventListener('change', updateRecurrenceFields);
            updateRecurrenceFields();
        });
    </script>
</x-app-layout>
