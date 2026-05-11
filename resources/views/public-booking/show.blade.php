<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Agendamento online AQAtende">
    <title>Agendar atendimento | AQAtende</title>
    <link rel="icon" type="image/png" href="{{ asset('logo.png') }}">
    @include('partials.pwa-meta')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 text-gray-900">
    <main class="mx-auto flex min-h-screen max-w-3xl flex-col px-4 py-6 md:py-10">
        <div class="mb-6 flex items-center gap-3">
            <img class="h-12 w-12 rounded-full border border-brand-100 bg-white object-contain" src="{{ asset('logo.png') }}" alt="AQAtende">
            <div>
                <div class="text-sm font-semibold uppercase tracking-[0.18em] text-brand-700">AQAtende</div>
                <h1 class="text-xl font-semibold text-gray-900">{{ $bookingLink->company?->name ?? 'Agendamento' }}</h1>
            </div>
        </div>

        <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-theme-sm md:p-6">
            <div class="mb-5">
                <div class="text-xs font-semibold uppercase tracking-[0.18em] text-brand-600">Agendamento online</div>
                <h2 class="mt-2 text-2xl font-semibold text-gray-900">Escolha o melhor horario</h2>
                <p class="mt-2 text-sm leading-6 text-gray-500">
                    Link gerado para {{ $bookingLink->patient?->full_name }}. Selecione servico, unidade, data e horario disponivel.
                </p>
            </div>

            @if ($errors->any())
                <div class="mb-4 rounded-lg border border-error-200 bg-error-50 px-4 py-3 text-sm text-error-700">
                    Nao foi possivel confirmar este horario. Escolha outro horario disponivel.
                </div>
            @endif

            <form method="GET" action="{{ route('public.booking.show', $bookingLink->token) }}" class="grid gap-4">
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700" for="service_id">Servico</label>
                    <select id="service_id" name="service_id" class="w-full rounded-lg border border-gray-200 bg-white px-3 py-3 text-sm text-gray-700 shadow-theme-xs focus:border-brand-300 focus:outline-none focus:ring-3 focus:ring-brand-500/10" onchange="this.form.submit()">
                        <option value="">Selecione um servico</option>
                        @foreach ($services as $service)
                            <option value="{{ $service->id }}" @selected($selectedService?->id === $service->id)>
                                {{ $service->name }} - {{ $service->duration_minutes }} min
                            </option>
                        @endforeach
                    </select>
                </div>

                @if ($selectedService)
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700" for="unit_id">Unidade</label>
                        <select id="unit_id" name="unit_id" class="w-full rounded-lg border border-gray-200 bg-white px-3 py-3 text-sm text-gray-700 shadow-theme-xs focus:border-brand-300 focus:outline-none focus:ring-3 focus:ring-brand-500/10" onchange="this.form.submit()">
                            <option value="">Selecione uma unidade</option>
                            @foreach ($units as $unit)
                                <option value="{{ $unit->id }}" @selected($selectedUnit?->id === $unit->id)>{{ $unit->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    @if (! $selectedService->is_package)
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700" for="professional_id">Profissional</label>
                            <select id="professional_id" name="professional_id" class="w-full rounded-lg border border-gray-200 bg-white px-3 py-3 text-sm text-gray-700 shadow-theme-xs focus:border-brand-300 focus:outline-none focus:ring-3 focus:ring-brand-500/10" onchange="this.form.submit()">
                                <option value="">Qualquer profissional disponivel</option>
                                @foreach ($professionals as $professional)
                                    <option value="{{ $professional->id }}" @selected($selectedProfessional?->id === $professional->id)>{{ $professional->display_name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <div>
                        <div class="mb-2 text-sm font-medium text-gray-700">Data</div>
                        <div class="grid gap-2" style="grid-template-columns: repeat(4, minmax(0, 1fr));">
                            @foreach ($availableDays as $day)
                                @php
                                    $isSelectedDay = $day->isSameDay($date);
                                    $dayUrl = route('public.booking.show', [
                                        'token' => $bookingLink->token,
                                        'service_id' => $selectedService?->id,
                                        'unit_id' => $selectedUnit?->id,
                                        'professional_id' => $selectedProfessional?->id,
                                        'date' => $day->toDateString(),
                                    ]);
                                @endphp
                                <a
                                    class="rounded-lg border px-2 py-3 text-center {{ $isSelectedDay ? 'border-brand-600 bg-brand-600 text-white shadow-theme-xs' : 'border-gray-200 bg-white text-gray-700 hover:border-brand-300 hover:bg-brand-50' }}"
                                    href="{{ $dayUrl }}"
                                >
                                    <span class="block text-[11px] font-medium {{ $isSelectedDay ? 'text-white/75' : 'text-gray-400' }}">{{ ucfirst($day->translatedFormat('D')) }}</span>
                                    <span class="mt-1 block text-lg font-semibold leading-none">{{ $day->format('d') }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            </form>

            @if ($selectedService && $selectedUnit)
                <form method="POST" action="{{ route('public.booking.store', $bookingLink->token) }}" class="mt-6 border-t border-gray-100 pt-5">
                    @csrf
                    <input type="hidden" name="service_id" value="{{ $selectedService->id }}">
                    <input type="hidden" name="unit_id" value="{{ $selectedUnit->id }}">

                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <h3 class="text-sm font-semibold text-gray-900">Horarios disponiveis</h3>
                            <p class="text-xs text-gray-500">{{ $date->translatedFormat('d \\d\\e F \\d\\e Y') }}</p>
                        </div>
                        <span class="rounded-full bg-brand-50 px-3 py-1 text-xs font-semibold text-brand-700">{{ $slots->count() }} horarios</span>
                    </div>

                    <div class="mt-4 grid gap-2 sm:grid-cols-2">
                        @forelse ($slots as $slot)
                            <label class="flex cursor-pointer items-center gap-3 rounded-lg border border-gray-200 px-3 py-3 text-sm hover:border-brand-300 hover:bg-brand-50">
                                <input type="radio" name="slot" value="{{ $slot['value'] }}" required class="h-4 w-4 text-brand-600">
                                <span>
                                    <span class="block font-semibold text-gray-900">{{ $slot['scheduled_at']->format('H:i') }}</span>
                                    <span class="block text-xs text-gray-500">{{ $slot['label'] }}</span>
                                </span>
                            </label>
                        @empty
                            <div class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-6 text-center text-sm text-gray-500 sm:col-span-2">
                                Nenhum horario disponivel nesta data. Escolha outro dia ou profissional.
                            </div>
                        @endforelse
                    </div>

                    @if ($slots->isNotEmpty())
                        <div class="mt-5">
                            <label class="mb-1 block text-sm font-medium text-gray-700" for="notes">Observacao opcional</label>
                            <textarea id="notes" name="notes" rows="3" class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 shadow-theme-xs focus:border-brand-300 focus:outline-none focus:ring-3 focus:ring-brand-500/10" placeholder="Alguma observacao para o atendimento?"></textarea>
                        </div>
                        <button class="mt-5 w-full rounded-lg bg-brand-600 px-4 py-3 text-sm font-semibold text-white shadow-theme-xs hover:bg-brand-700" type="submit">
                            Confirmar agendamento
                        </button>
                    @endif
                </form>
            @endif
        </section>
    </main>
</body>
</html>
