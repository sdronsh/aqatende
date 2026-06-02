<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Agendamento confirmado AQAtende">
    <title>Agendamento confirmado | AQAtende</title>
    <link rel="icon" type="image/png" href="{{ asset('icons/icon-192.png') }}">
    @include('partials.pwa-meta')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 text-gray-900">
    <main class="mx-auto flex min-h-screen max-w-xl items-center px-4 py-8">
        <section class="w-full rounded-xl border border-gray-200 bg-white p-6 text-center shadow-theme-sm">
            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-success-50 text-success-700">
                <svg class="h-7 w-7" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.78-9.22a.75.75 0 0 0-1.06-1.06L9 11.44 7.28 9.72a.75.75 0 0 0-1.06 1.06l2.25 2.25a.75.75 0 0 0 1.06 0l4.25-4.25Z" clip-rule="evenodd" />
                </svg>
            </div>
            <h1 class="mt-5 text-2xl font-semibold text-gray-900">Agendamento confirmado</h1>
            <p class="mt-2 text-sm leading-6 text-gray-500">
                {{ $appointment->patient?->full_name }}, seu horario foi registrado em {{ $company?->name ?? 'AQAtende' }}.
            </p>

            @php
                $confirmedAppointments = collect([$previousAppointment ?? null, $appointment])->filter();
            @endphp

            <div class="mt-6 grid gap-3 rounded-lg bg-gray-50 p-4 text-left text-sm">
                @foreach ($confirmedAppointments as $confirmedAppointment)
                    <div class="rounded-lg border border-white bg-white p-3 shadow-theme-xs">
                        <div class="font-semibold text-gray-900">{{ $confirmedAppointment->serviceNames() }}</div>
                        <div class="mt-2 grid gap-1 text-xs text-gray-500">
                            <div class="flex justify-between gap-3">
                                <span>Horario</span>
                                <span class="font-semibold text-gray-900">
                                    {{ $confirmedAppointment->scheduled_at->format('d/m/Y H:i') }}
                                    @if ($confirmedAppointment->ends_at)
                                        ate {{ $confirmedAppointment->ends_at->format('H:i') }}
                                    @endif
                                </span>
                            </div>
                            <div class="flex justify-between gap-3">
                                <span>Profissional</span>
                                <span class="font-semibold text-gray-900">{{ $confirmedAppointment->professional?->display_name }}</span>
                            </div>
                            <div class="flex justify-between gap-3">
                                <span>Unidade</span>
                                <span class="font-semibold text-gray-900">{{ $confirmedAppointment->unit?->name }}</span>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-6 grid gap-3 sm:grid-cols-2">
                <a
                    class="inline-flex items-center justify-center rounded-lg border border-brand-200 bg-white px-4 py-3 text-sm font-semibold text-brand-700 shadow-theme-xs hover:bg-brand-50"
                    href="{{ $newBookingUrl }}"
                >
                    Incluir novo agendamento
                </a>
                <button
                    class="inline-flex items-center justify-center rounded-lg bg-brand-600 px-4 py-3 text-sm font-semibold text-white shadow-theme-xs hover:bg-brand-700"
                    type="button"
                    data-finish-booking
                >
                    Finalizar
                </button>
            </div>
        </section>
    </main>
    <dialog id="booking-finished-dialog" class="m-auto w-[calc(100%-2rem)] max-w-md rounded-xl border border-gray-200 p-0 shadow-theme-lg">
        <div class="p-5 text-center">
            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-success-50 text-success-700">
                <svg class="h-6 w-6" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.78-9.22a.75.75 0 0 0-1.06-1.06L9 11.44 7.28 9.72a.75.75 0 0 0-1.06 1.06l2.25 2.25a.75.75 0 0 0 1.06 0l4.25-4.25Z" clip-rule="evenodd" />
                </svg>
            </div>
            <h2 class="mt-4 text-lg font-semibold text-gray-900">Agendamento realizado</h2>
            <p class="mt-2 text-sm leading-6 text-gray-500">
                Seu agendamento foi confirmado com sucesso.
            </p>
            <button
                class="mt-5 w-full rounded-lg bg-brand-600 px-4 py-3 text-sm font-semibold text-white shadow-theme-xs hover:bg-brand-700"
                type="button"
                data-close-after-message
            >
                OK
            </button>
        </div>
    </dialog>
    <script>
        const closeBookingWindow = () => {
            window.close();

            if (!window.closed) {
                window.location.href = 'about:blank';
            }
        };

        const finishedDialog = document.getElementById('booking-finished-dialog');

        document.querySelector('[data-finish-booking]')?.addEventListener('click', () => {
            if (finishedDialog?.showModal) {
                finishedDialog.showModal();
                return;
            }

            closeBookingWindow();
        });

        document.querySelector('[data-close-after-message]')?.addEventListener('click', () => {
            closeBookingWindow();
        });
    </script>
</body>
</html>
