<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Assinatura {{ $plan['name'] }} | AQAtende</title>
    <link rel="icon" type="image/png" href="{{ asset('logo.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        .billing-shell {
            display: grid;
            grid-template-columns: 340px minmax(0, 1fr);
            gap: 24px;
            align-items: start;
        }
        .billing-section {
            border: 1px solid #eaecf0;
            border-radius: 16px;
            padding: 20px;
            background: #fff;
        }
        .billing-grid {
            display: grid;
            grid-template-columns: repeat(12, minmax(0, 1fr));
            gap: 16px;
        }
        .billing-field {
            min-width: 0;
        }
        .billing-field label {
            display: block;
            margin-bottom: 6px;
            font-size: 13px;
            font-weight: 600;
            color: #344054;
        }
        .billing-field input,
        .billing-field select,
        .billing-field textarea {
            width: 100%;
            border: 1px solid #d0d5dd;
            border-radius: 10px;
            background: #fff;
            padding: 0 12px;
            font-size: 14px;
            color: #101828;
            box-shadow: 0 1px 2px rgba(16, 24, 40, .05);
            outline: none;
        }
        .billing-field input,
        .billing-field select {
            height: 44px;
        }
        .billing-field textarea {
            min-height: 92px;
            padding-top: 10px;
            resize: vertical;
        }
        .billing-field input:focus,
        .billing-field select:focus,
        .billing-field textarea:focus {
            border-color: #b12ca0;
            box-shadow: 0 0 0 4px rgba(177, 44, 160, .12);
        }
        .span-3 { grid-column: span 3 / span 3; }
        .span-4 { grid-column: span 4 / span 4; }
        .span-6 { grid-column: span 6 / span 6; }
        .span-12 { grid-column: span 12 / span 12; }
        @media (max-width: 1024px) {
            .billing-shell {
                grid-template-columns: 1fr;
            }
        }
        @media (max-width: 720px) {
            .billing-grid {
                grid-template-columns: 1fr;
            }
            .span-3,
            .span-4,
            .span-6,
            .span-12 {
                grid-column: 1 / -1;
            }
        }
    </style>
</head>
<body class="bg-gray-50 text-gray-900">
    <main class="min-h-screen px-5 py-8">
        <div class="mx-auto max-w-6xl">
            <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
                <a href="{{ url('/#planos') }}" class="flex items-center gap-3">
                    <img class="h-12 w-12 object-contain" src="{{ asset('logo.png') }}" alt="AQAtende">
                    <span class="text-sm font-semibold uppercase tracking-[0.24em] text-brand-700">AQAtende</span>
                </a>
                <a class="rounded-full border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-theme-xs hover:bg-gray-100" href="{{ route('subscriptions.create', $plan['slug']) }}">Voltar</a>
            </div>

            @if (session('status'))
                <div class="mb-4 rounded-xl border border-success-200 bg-success-50 px-4 py-3 text-sm text-success-700">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-4 rounded-xl border border-error-200 bg-error-50 px-4 py-3 text-sm text-error-700">
                    {{ $errors->first() }}
                </div>
            @endif

            <div class="billing-shell">
                <aside class="rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-sm">
                    <div class="text-xs font-bold uppercase tracking-[0.24em] text-brand-600">Etapa 2 de 2</div>
                    <h1 class="mt-4 text-2xl font-semibold text-gray-900">Assinatura</h1>
                    <div class="mt-5 rounded-xl border border-brand-100 bg-brand-50 p-4">
                        <div class="text-xs font-semibold uppercase text-brand-700">Licenca</div>
                        <div class="mt-1 text-2xl font-semibold text-brand-800">#{{ $pending['license_id'] }}</div>
                        <div class="mt-2 text-sm text-brand-700">{{ $pending['company_name'] ?? 'Empresa cadastrada' }}</div>
                    </div>
                    <div class="mt-6 space-y-3 text-sm text-gray-600">
                        <div class="flex justify-between gap-4 border-b border-gray-100 pb-3">
                            <span>Plano</span>
                            <strong class="text-gray-800">{{ $plan['name'] }}</strong>
                        </div>
                        <div class="flex justify-between gap-4 border-b border-gray-100 pb-3">
                            <span>Mensalidade</span>
                            <strong class="text-gray-800">R$ {{ number_format($plan['amount'], 2, ',', '.') }}</strong>
                        </div>
                        <div class="flex justify-between gap-4 border-b border-gray-100 pb-3">
                            <span>Profissionais</span>
                            <strong class="text-gray-800">{{ $plan['professional_limit'] ? 'Ate '.$plan['professional_limit'] : 'Sem limite' }}</strong>
                        </div>
                    </div>
                </aside>

                <form method="POST" action="{{ route('subscriptions.billing.store', $plan['slug']) }}" class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-sm md:p-6">
                    @csrf
                    <div class="billing-section">
                        <h2 class="mb-4 text-lg font-semibold text-gray-900">Dados de vencimento</h2>
                        <div class="billing-grid">
                            <div class="billing-field span-12">
                                <label for="due_day">Dia de vencimento</label>
                                <select id="due_day" name="due_day" required>
                                    @for ($day = 1; $day <= 31; $day++)
                                        <option value="{{ $day }}" @selected((int) old('due_day', 10) === $day)>{{ $day }}</option>
                                    @endfor
                                </select>
                            </div>
                            <div class="billing-field span-12">
                                <label for="notes">Observacoes</label>
                                <textarea id="notes" name="notes">{{ old('notes', 'Assinatura criada via site.') }}</textarea>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 flex flex-wrap justify-end gap-3 border-t border-gray-100 pt-5">
                        <a class="inline-flex rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-theme-xs hover:bg-gray-50" href="{{ route('subscriptions.create', $plan['slug']) }}">Voltar</a>
                        <button class="inline-flex rounded-lg bg-brand-500 px-5 py-2 text-sm font-semibold text-white shadow-theme-xs hover:bg-brand-600" type="submit">Gerar pagamento</button>
                    </div>
                </form>
            </div>
        </div>
    </main>
</body>
</html>
