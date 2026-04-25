<x-app-layout>
    <x-slot name="header">
        @php
            $titleColor = session('active_company_id') ? '#4682B4' : '#228B22';
            $formatMoney = fn ($cents) => 'R$ ' . number_format(($cents ?? 0) / 100, 2, ',', '.');
            $periodText = $period === 'day' ? 'do dia' : ($period === 'week' ? 'da semana' : 'do mes');
            $appointmentsDelta = $appointmentsPrevCount > 0
                ? (int) round((($appointmentsPeriodCount - $appointmentsPrevCount) / $appointmentsPrevCount) * 100)
                : ($appointmentsPeriodCount > 0 ? 100 : 0);
            $appointmentsDeltaLabel = ($appointmentsDelta >= 0 ? '+' : '') . $appointmentsDelta . '%';
            $appointmentsDeltaClass = $appointmentsDelta >= 0
                ? 'bg-success-50 text-success-700'
                : 'bg-error-50 text-error-700';
            $confirmedPercent = $appointmentsPeriodCount > 0 ? (int) round(($confirmedCount / $appointmentsPeriodCount) * 100) : 0;
            $cancelledPercent = $appointmentsPeriodCount > 0 ? (int) round(($cancelledCount / $appointmentsPeriodCount) * 100) : 0;
            $patientsDeltaLabel = $patientsNewCount > 0 ? '+'. $patientsNewCount : '0';
            $selectedClinicName = $selectedClinicId ? ($clinics->firstWhere('id', $selectedClinicId)?->name) : null;
            if (! $selectedClinicName && $clinics->count() === 1) {
                $selectedClinicName = $clinics->first()->name;
            }
            $dashboardTitle = $selectedClinicName ? $selectedClinicName : 'Dashboard';
        @endphp
        <h2 class="text-lg font-semibold" style="color: {{ $titleColor }};">{{ $dashboardTitle }}</h2>
    </x-slot>

    <div class="space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="text-sm text-gray-500">Visao geral da operacao e financeiro ({{ $rangeLabel }}).</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <form method="GET" class="flex flex-wrap items-center gap-2">
                    <input type="hidden" name="period" value="{{ $period }}" />
                    <select class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 shadow-theme-xs focus:border-brand-300 focus:outline-none focus:ring-3 focus:ring-brand-500/10" name="clinic_id">
                        <option value="">Todas as clinicas</option>
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
                    <button class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-theme-xs hover:bg-gray-50" type="submit">Aplicar</button>
                </form>
                <div class="inline-flex rounded-lg border border-gray-200 bg-white p-1 text-sm">
                    <a class="rounded-md px-3 py-1.5 {{ $period === 'day' ? 'bg-brand-50 text-brand-600' : 'text-gray-600 hover:bg-gray-50' }}" href="{{ route('dashboard', ['period' => 'day', 'clinic_id' => $selectedClinicId, 'unit_id' => $selectedUnitId, 'professional_id' => $selectedProfessionalId]) }}">Hoje</a>
                    <a class="rounded-md px-3 py-1.5 {{ $period === 'week' ? 'bg-brand-50 text-brand-600' : 'text-gray-600 hover:bg-gray-50' }}" href="{{ route('dashboard', ['period' => 'week', 'clinic_id' => $selectedClinicId, 'unit_id' => $selectedUnitId, 'professional_id' => $selectedProfessionalId]) }}">Semana</a>
                    <a class="rounded-md px-3 py-1.5 {{ $period === 'month' ? 'bg-brand-50 text-brand-600' : 'text-gray-600 hover:bg-gray-50' }}" href="{{ route('dashboard', ['period' => 'month', 'clinic_id' => $selectedClinicId, 'unit_id' => $selectedUnitId, 'professional_id' => $selectedProfessionalId]) }}">Mes</a>
                </div>
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-theme-sm">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-gray-700">Consultas {{ $periodText }}</h3>
                    <span class="rounded-full px-2 py-0.5 text-xs font-semibold {{ $appointmentsDeltaClass }}">{{ $appointmentsDeltaLabel }}</span>
                </div>
                <div class="mt-3 text-2xl font-semibold text-gray-900">{{ $appointmentsPeriodCount }}</div>
                <p class="mt-1 text-xs text-gray-500">comparado ao periodo anterior</p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-theme-sm">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-gray-700">Confirmadas</h3>
                    <span class="rounded-full bg-brand-50 px-2 py-0.5 text-xs font-semibold text-brand-700">{{ $confirmedPercent }}%</span>
                </div>
                <div class="mt-3 text-2xl font-semibold text-gray-900">{{ $confirmedCount }}</div>
                <p class="mt-1 text-xs text-gray-500">agendamentos {{ $periodText }}</p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-theme-sm">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-gray-700">Canceladas</h3>
                    <span class="rounded-full bg-error-50 px-2 py-0.5 text-xs font-semibold text-error-700">{{ $cancelledPercent }}%</span>
                </div>
                <div class="mt-3 text-2xl font-semibold text-gray-900">{{ $cancelledCount }}</div>
                <p class="mt-1 text-xs text-gray-500">agendamentos {{ $periodText }}</p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-theme-sm">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-gray-700">Clientes novos</h3>
                    <span class="rounded-full bg-brand-50 px-2 py-0.5 text-xs font-semibold text-brand-700">{{ $patientsDeltaLabel }}</span>
                </div>
                <div class="mt-3 text-2xl font-semibold text-gray-900">{{ $patientsNewCount }}</div>
                <p class="mt-1 text-xs text-gray-500">cadastros {{ $periodText }}</p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-theme-sm">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-gray-700">Ocupacao agenda</h3>
                    <span class="rounded-full bg-warning-50 px-2 py-0.5 text-xs font-semibold text-warning-700">{{ $occupancyPercent }}%</span>
                </div>
                <div class="mt-3 text-2xl font-semibold text-gray-900">{{ number_format($occupiedHours, 1, ',', '.') }}h</div>
                <p class="mt-1 text-xs text-gray-500">horas alocadas {{ $periodText }}</p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-theme-sm">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-gray-700">Receita {{ $periodText }}</h3>
                    <span class="rounded-full bg-success-50 px-2 py-0.5 text-xs font-semibold text-success-700">{{ $formatMoney($receitaCents) }}</span>
                </div>
                <div class="mt-3 text-2xl font-semibold text-gray-900">{{ $formatMoney($receitaCents) }}</div>
                <p class="mt-1 text-xs text-gray-500">pagamentos confirmados</p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-theme-sm">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-gray-700">Contas a receber</h3>
                    <span class="rounded-full bg-brand-50 px-2 py-0.5 text-xs font-semibold text-brand-700">{{ $receberVencidas }} vencidas</span>
                </div>
                <div class="mt-3 text-2xl font-semibold text-gray-900">{{ $formatMoney($receberAbertoCents) }}</div>
                <p class="mt-1 text-xs text-gray-500">em aberto</p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-theme-sm">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-gray-700">Contas a pagar</h3>
                    <span class="rounded-full bg-error-50 px-2 py-0.5 text-xs font-semibold text-error-700">{{ $pagarVencidas }} vencidas</span>
                </div>
                <div class="mt-3 text-2xl font-semibold text-gray-900">{{ $formatMoney($pagarAbertasCents) }}</div>
                <p class="mt-1 text-xs text-gray-500">proximos 7 dias</p>
            </div>
        </div>

        <div class="grid gap-4 lg:grid-cols-3">
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-theme-sm lg:col-span-2">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-gray-800">Receita x Atendimentos</h3>
                    <span class="text-xs text-gray-500">Ultimos 30 dias</span>
                </div>
                <div class="mt-4 rounded-lg border border-gray-100 bg-gray-50 p-4">
                    @php
                        $left = 20;
                        $right = 580;
                        $top = 30;
                        $bottom = 140;
                        $width = $right - $left;
                        $height = $bottom - $top;
                        $buildPath = function ($values) use ($left, $bottom, $width, $height) {
                            $count = count($values);
                            if ($count < 2) {
                                return '';
                            }
                            $maxValue = max($values);
                            $maxValue = $maxValue > 0 ? $maxValue : 1;
                            $step = $width / ($count - 1);
                            $points = [];
                            foreach ($values as $index => $value) {
                                $x = $left + ($index * $step);
                                $ratio = $value / $maxValue;
                                $y = $bottom - ($ratio * $height);
                                $points[] = number_format($x, 2, '.', '') . ' ' . number_format($y, 2, '.', '');
                            }
                            return 'M ' . implode(' L ', $points);
                        };
                        $appointmentsPath = $buildPath($appointmentsChart);
                        $revenuePath = $buildPath($revenueChart);
                    @endphp
                    <svg class="h-36 w-full" viewBox="0 0 600 160" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M20 120H580" stroke="#E5E7EB" stroke-dasharray="4 6"/>
                        <path d="M20 80H580" stroke="#E5E7EB" stroke-dasharray="4 6"/>
                        <path d="M20 40H580" stroke="#E5E7EB" stroke-dasharray="4 6"/>
                        @if ($revenuePath)
                            <path d="{{ $revenuePath }}" stroke="#3B82F6" stroke-width="3" />
                        @endif
                        @if ($appointmentsPath)
                            <path d="{{ $appointmentsPath }}" stroke="#22C55E" stroke-width="3" />
                        @endif
                    </svg>
                    <div class="mt-3 flex flex-wrap items-center gap-4 text-xs text-gray-500">
                        <span class="inline-flex items-center gap-2"><span class="h-2 w-2 rounded-full bg-brand-500"></span>Receita</span>
                        <span class="inline-flex items-center gap-2"><span class="h-2 w-2 rounded-full bg-green-500"></span>Atendimentos</span>
                    </div>
                </div>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-theme-sm">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-gray-800">Status do dia</h3>
                    <span class="text-xs text-gray-500">Hoje</span>
                </div>
                <div class="mt-4 grid gap-3">
                    <div class="flex items-center justify-between rounded-lg border border-gray-100 bg-gray-50 px-3 py-2 text-sm text-gray-600">
                        <span>Confirmadas</span>
                        <span class="font-semibold text-gray-800">{{ $statusToday['confirmadas'] }}</span>
                    </div>
                    <div class="flex items-center justify-between rounded-lg border border-gray-100 bg-gray-50 px-3 py-2 text-sm text-gray-600">
                        <span>Atendidas</span>
                        <span class="font-semibold text-gray-800">{{ $statusToday['atendidas'] }}</span>
                    </div>
                    <div class="flex items-center justify-between rounded-lg border border-gray-100 bg-gray-50 px-3 py-2 text-sm text-gray-600">
                        <span>Canceladas</span>
                        <span class="font-semibold text-gray-800">{{ $statusToday['canceladas'] }}</span>
                    </div>
                    <div class="flex items-center justify-between rounded-lg border border-gray-100 bg-gray-50 px-3 py-2 text-sm text-gray-600">
                        <span>Faltas</span>
                        <span class="font-semibold text-gray-800">{{ $statusToday['faltas'] }}</span>
                    </div>
                </div>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-theme-sm lg:col-span-3">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-gray-800">Agendamentos por profissional</h3>
                    <span class="text-xs text-gray-500">{{ $periodLabels[$period] }}</span>
                </div>
                <div class="mt-4 grid gap-3">
                    @forelse ($professionalStats as $index => $stat)
                        @php
                            $barColors = ['bg-brand-500', 'bg-brand-500', 'bg-success-500', 'bg-warning-500'];
                            $barClass = $barColors[$index] ?? 'bg-brand-500';
                        @endphp
                        <div class="flex items-center gap-4">
                            <span class="w-28 text-xs text-gray-500">{{ $stat['name'] }}</span>
                            <div class="h-2 flex-1 overflow-hidden rounded-full bg-gray-100">
                                <div class="h-full rounded-full {{ $barClass }}" style="width: {{ $stat['percent'] }}%;"></div>
                            </div>
                            <span class="text-xs font-semibold text-gray-700">{{ $stat['total'] }}</span>
                        </div>
                    @empty
                        <div class="rounded-lg border border-gray-100 bg-gray-50 px-3 py-4 text-sm text-gray-500">
                            Nenhum agendamento encontrado para o periodo.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
