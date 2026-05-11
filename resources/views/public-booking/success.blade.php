<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Agendamento confirmado AQAtende">
    <title>Agendamento confirmado | AQAtende</title>
    <link rel="icon" type="image/png" href="{{ asset('logo.png') }}">
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

            <div class="mt-6 grid gap-2 rounded-lg bg-gray-50 p-4 text-left text-sm">
                <div class="flex justify-between gap-3">
                    <span class="text-gray-500">Data</span>
                    <span class="font-semibold text-gray-900">{{ $appointment->scheduled_at->format('d/m/Y H:i') }}</span>
                </div>
                <div class="flex justify-between gap-3">
                    <span class="text-gray-500">Servico</span>
                    <span class="font-semibold text-gray-900">{{ $appointment->serviceNames() }}</span>
                </div>
                <div class="flex justify-between gap-3">
                    <span class="text-gray-500">Profissional</span>
                    <span class="font-semibold text-gray-900">{{ $appointment->professional?->display_name }}</span>
                </div>
                <div class="flex justify-between gap-3">
                    <span class="text-gray-500">Unidade</span>
                    <span class="font-semibold text-gray-900">{{ $appointment->unit?->name }}</span>
                </div>
            </div>
        </section>
    </main>
    @include('partials.pwa-install-prompt')
</body>
</html>
