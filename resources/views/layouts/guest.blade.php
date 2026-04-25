<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="description" content="Sistema completo para gestão de salões: agenda, fila, clientes, profissionais e financeiro.">

        <title>{{ config('app.name', 'Laravel') }}</title>
        <link rel="icon" type="image/png" href="{{ asset('logo.png') }}">
        <link rel="shortcut icon" type="image/png" href="{{ asset('logo.png') }}">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=manrope:400,500,600,700&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body>
        @php
            $statusKey = session('status');
            $statusMessage = $statusKey;
            if (is_string($statusKey)) {
                $statusMessage = match ($statusKey) {
                    'verification-link-sent' => 'Um novo link de verificacao foi enviado.',
                    'password-reset' => 'Sua senha foi redefinida.',
                    default => $statusKey,
                };
            }
        @endphp
        @if ($statusKey || session('error') || session('warning') || session('info') || $errors->any())
            <div class="fixed right-4 top-4 z-999999 flex w-full max-w-sm flex-col gap-3" style="z-index: 1000000;">
                @if ($statusKey)
                    <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 shadow-lg" role="alert">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-start gap-2">
                                <span class="mt-0.5 text-emerald-600">
                                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.78-9.22a.75.75 0 00-1.06-1.06L9 11.44 7.28 9.72a.75.75 0 10-1.06 1.06l2.25 2.25a.75.75 0 001.06 0l4.25-4.25z" clip-rule="evenodd" />
                                    </svg>
                                </span>
                                <div>{{ $statusMessage }}</div>
                            </div>
                            <button class="text-emerald-700/70 hover:text-emerald-800" type="button" data-dismiss-toast>&times;</button>
                        </div>
                    </div>
                @endif
                @if (session('info'))
                    <div class="rounded-lg border border-brand-200 bg-brand-50 px-4 py-3 text-sm text-brand-800 shadow-lg" role="alert">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-start gap-2">
                                <span class="mt-0.5 text-brand-600">
                                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm.75-11a.75.75 0 11-1.5 0 .75.75 0 011.5 0zM9 9.5a.75.75 0 011.5 0v5a.75.75 0 01-1.5 0v-5z" clip-rule="evenodd" />
                                    </svg>
                                </span>
                                <div>{{ session('info') }}</div>
                            </div>
                            <button class="text-brand-800/70 hover:text-brand-900" type="button" data-dismiss-toast>&times;</button>
                        </div>
                    </div>
                @endif
                @if (session('warning'))
                    <div class="rounded-lg border border-warning-200 bg-warning-50 px-4 py-3 text-sm text-warning-800 shadow-lg" role="alert">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-start gap-2">
                                <span class="mt-0.5 text-warning-600">
                                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l6.518 11.59c.75 1.334-.213 3.011-1.742 3.011H3.48c-1.53 0-2.492-1.677-1.742-3.01l6.52-11.59zM11 13a1 1 0 10-2 0 1 1 0 002 0zm-1-7a.75.75 0 00-.75.75v4a.75.75 0 001.5 0v-4A.75.75 0 0010 6z" clip-rule="evenodd" />
                                    </svg>
                                </span>
                                <div>{{ session('warning') }}</div>
                            </div>
                            <button class="text-warning-800/70 hover:text-warning-900" type="button" data-dismiss-toast>&times;</button>
                        </div>
                    </div>
                @endif
                @if (session('error'))
                    <div class="rounded-lg border border-error-200 bg-error-50 px-4 py-3 text-sm text-error-700 shadow-lg" role="alert">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-start gap-2">
                                <span class="mt-0.5 text-error-600">
                                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm-1-9.75a1 1 0 112 0v4.5a1 1 0 11-2 0v-4.5zM10 15a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                                    </svg>
                                </span>
                                <div>{{ session('error') }}</div>
                            </div>
                            <button class="text-error-700/70 hover:text-error-800" type="button" data-dismiss-toast>&times;</button>
                        </div>
                    </div>
                @endif
                @if ($errors->any())
                    <div class="rounded-lg border border-error-200 bg-error-50 px-4 py-3 text-sm text-error-700 shadow-lg" role="alert">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-start gap-2">
                                <span class="mt-0.5 text-error-600">
                                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm-1-9.75a1 1 0 112 0v4.5a1 1 0 11-2 0v-4.5zM10 15a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                                    </svg>
                                </span>
                                <ul class="list-disc pl-5">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                            <button class="text-error-700/70 hover:text-error-800" type="button" data-dismiss-toast>&times;</button>
                        </div>
                    </div>
                @endif
            </div>
        @endif

        @php $fullBleed = $fullBleed ?? false; @endphp
        @if ($fullBleed)
            <div style="font-family: 'Manrope', sans-serif;">
                {{ $slot }}
            </div>
        @else
            <div class="min-h-screen flex items-center justify-center bg-gray-50 px-4" style="font-family: 'Manrope', sans-serif;">
                <div class="w-full max-w-md">
                    <div class="mb-6 flex items-center justify-center">
                        <img style="height: 220px; width: auto;" src="{{ asset('logo.png') }}" alt="AQAtende">
                    </div>
                    <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-theme-sm">
                        {{ $slot }}
                    </div>
                </div>
            </div>
        @endif

        <script>
            const toasts = document.querySelectorAll('[data-dismiss-toast]');
            toasts.forEach((btn) => {
                btn.addEventListener('click', () => {
                    const toast = btn.closest('[role="alert"]');
                    toast?.remove();
                });
            });

            setTimeout(() => {
                document.querySelectorAll('[role="alert"]').forEach((toast) => toast.remove());
            }, 5000);

            const formatCnpj = (value) => {
                const digits = value.replace(/\D/g, '').slice(0, 14);
                if (digits.length <= 11) {
                    if (digits.length <= 3) return digits;
                    if (digits.length <= 6) return `${digits.slice(0, 3)}.${digits.slice(3)}`;
                    if (digits.length <= 9) return `${digits.slice(0, 3)}.${digits.slice(3, 6)}.${digits.slice(6)}`;
                    return `${digits.slice(0, 3)}.${digits.slice(3, 6)}.${digits.slice(6, 9)}-${digits.slice(9, 11)}`;
                }
                if (digits.length <= 2) return digits;
                if (digits.length <= 5) return `${digits.slice(0, 2)}.${digits.slice(2)}`;
                if (digits.length <= 8) return `${digits.slice(0, 2)}.${digits.slice(2, 5)}.${digits.slice(5)}`;
                if (digits.length <= 12) return `${digits.slice(0, 2)}.${digits.slice(2, 5)}.${digits.slice(5, 8)}/${digits.slice(8)}`;
                return `${digits.slice(0, 2)}.${digits.slice(2, 5)}.${digits.slice(5, 8)}/${digits.slice(8, 12)}-${digits.slice(12, 14)}`;
            };

            document.addEventListener('input', (event) => {
                const target = event.target;
                if (!target || target.dataset?.mask !== 'cnpj') return;
                target.value = formatCnpj(target.value);
            });
        </script>
    </body>
</html>
