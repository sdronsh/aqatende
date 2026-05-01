<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Contratar {{ $plan['name'] }} | AQAtende</title>
    <link rel="icon" type="image/png" href="{{ asset('logo.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        .subscription-shell {
            display: grid;
            grid-template-columns: 340px minmax(0, 1fr);
            gap: 24px;
            align-items: start;
        }
        .subscription-section {
            border: 1px solid #eaecf0;
            border-radius: 16px;
            padding: 20px;
            background: #fff;
        }
        .subscription-section + .subscription-section {
            margin-top: 18px;
        }
        .subscription-section-title {
            margin-bottom: 16px;
            font-size: 16px;
            font-weight: 700;
            color: #101828;
        }
        .subscription-grid {
            display: grid;
            grid-template-columns: repeat(12, minmax(0, 1fr));
            gap: 16px;
        }
        .subscription-field {
            min-width: 0;
        }
        .subscription-field label {
            display: block;
            margin-bottom: 6px;
            font-size: 13px;
            font-weight: 600;
            color: #344054;
        }
        .subscription-field input {
            width: 100%;
            height: 44px;
            border: 1px solid #d0d5dd;
            border-radius: 10px;
            background: #fff;
            padding: 0 12px;
            font-size: 14px;
            color: #101828;
            box-shadow: 0 1px 2px rgba(16, 24, 40, .05);
            outline: none;
        }
        .subscription-field input:focus {
            border-color: #b12ca0;
            box-shadow: 0 0 0 4px rgba(177, 44, 160, .12);
        }
        .span-2 { grid-column: span 2 / span 2; }
        .span-3 { grid-column: span 3 / span 3; }
        .span-4 { grid-column: span 4 / span 4; }
        .span-5 { grid-column: span 5 / span 5; }
        .span-6 { grid-column: span 6 / span 6; }
        .span-7 { grid-column: span 7 / span 7; }
        .span-8 { grid-column: span 8 / span 8; }
        .span-12 { grid-column: span 12 / span 12; }
        @media (max-width: 1024px) {
            .subscription-shell {
                grid-template-columns: 1fr;
            }
        }
        @media (max-width: 720px) {
            .subscription-grid {
                grid-template-columns: 1fr;
            }
            .span-2,
            .span-3,
            .span-4,
            .span-5,
            .span-6,
            .span-7,
            .span-8,
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
                <a class="rounded-full border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-theme-xs hover:bg-gray-100" href="{{ url('/#planos') }}">Voltar aos planos</a>
            </div>

            @if (session('status'))
                <div class="mb-4 rounded-xl border border-success-200 bg-success-50 px-4 py-3 text-sm text-success-700">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-4 rounded-xl border border-error-200 bg-error-50 px-4 py-3 text-sm text-error-700">
                    @if ($errors->count() === 1)
                        {{ $errors->first() }}
                    @else
                        <ul class="list-disc space-y-1 pl-5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            @endif

            <div class="subscription-shell">
                <aside class="rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-sm">
                    <div class="text-xs font-bold uppercase tracking-[0.24em] text-brand-600">Plano selecionado</div>
                    <h1 class="mt-4 text-2xl font-semibold text-gray-900">{{ $plan['name'] }}</h1>
                    <div class="mt-4 text-4xl font-semibold text-gray-900">R$ {{ number_format($plan['amount'], 2, ',', '.') }}</div>
                    <div class="mt-1 text-sm font-medium text-gray-500">mensal</div>
                    <div class="mt-6 space-y-3 text-sm text-gray-600">
                        <div class="flex justify-between gap-4 border-b border-gray-100 pb-3">
                            <span>Profissionais</span>
                            <strong class="text-gray-800">{{ $plan['professional_limit'] ? 'Ate '.$plan['professional_limit'] : 'Sem limite' }}</strong>
                        </div>
                        <div class="flex justify-between gap-4 border-b border-gray-100 pb-3">
                            <span>Empresas</span>
                            <strong class="text-gray-800">{{ $plan['company_limit'] }}</strong>
                        </div>
                        <div class="flex justify-between gap-4 border-b border-gray-100 pb-3">
                            <span>Unidades</span>
                            <strong class="text-gray-800">{{ $plan['unit_limit'] }}</strong>
                        </div>
                    </div>
                    <p class="mt-6 text-sm leading-6 text-gray-500">
                        Esses dados serao enviados automaticamente para o sistema de licencas. Na proxima etapa voce define o vencimento da assinatura.
                    </p>
                </aside>

                <form method="POST" action="{{ route('subscriptions.store', $plan['slug']) }}" class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-sm md:p-6">
                    @csrf
                    <div class="subscription-section">
                        <h2 class="subscription-section-title">Dados da empresa</h2>
                        <div class="subscription-grid">
                            <div class="subscription-field span-7">
                                <label for="name">Nome da empresa</label>
                                <input id="name" name="name" value="{{ old('name') }}" required>
                            </div>
                            <div class="subscription-field span-5">
                                <label for="cnpj">CNPJ</label>
                                <input id="cnpj" name="cnpj" value="{{ old('cnpj') }}" placeholder="00.000.000/0000-00" required>
                            </div>
                            <div class="subscription-field span-7">
                                <label for="email">E-mail financeiro</label>
                                <input id="email" type="email" name="email" value="{{ old('email') }}" required>
                            </div>
                            <div class="subscription-field span-5">
                                <label for="phone">Telefone</label>
                                <input id="phone" name="phone" value="{{ old('phone') }}" placeholder="(00) 00000-0000">
                            </div>
                        </div>
                    </div>

                    <div class="subscription-section">
                        <h2 class="subscription-section-title">Contato responsavel</h2>
                        <div class="subscription-grid">
                            <div class="subscription-field span-4">
                                <label for="contact_name">Nome</label>
                                <input id="contact_name" name="contact_name" value="{{ old('contact_name') }}" required>
                            </div>
                            <div class="subscription-field span-4">
                                <label for="contact_email">E-mail</label>
                                <input id="contact_email" type="email" name="contact_email" value="{{ old('contact_email') }}" required>
                            </div>
                            <div class="subscription-field span-4">
                                <label for="contact_phone">Telefone</label>
                                <input id="contact_phone" name="contact_phone" value="{{ old('contact_phone') }}" placeholder="(00) 00000-0000">
                            </div>
                        </div>
                    </div>

                    <div class="subscription-section">
                        <h2 class="subscription-section-title">Endereco</h2>
                        <div class="subscription-grid">
                            <div class="subscription-field span-3">
                                <label for="address_zip">CEP</label>
                                <input id="address_zip" name="address_zip" value="{{ old('address_zip') }}" placeholder="00000-000">
                            </div>
                            <div class="subscription-field span-7">
                                <label for="address_street">Logradouro</label>
                                <input id="address_street" name="address_street" value="{{ old('address_street') }}">
                            </div>
                            <div class="subscription-field span-2">
                                <label for="address_number">Numero</label>
                                <input id="address_number" name="address_number" value="{{ old('address_number') }}">
                            </div>
                            <div class="subscription-field span-4">
                                <label for="address_complement">Complemento</label>
                                <input id="address_complement" name="address_complement" value="{{ old('address_complement') }}">
                            </div>
                            <div class="subscription-field span-4">
                                <label for="address_neighborhood">Bairro</label>
                                <input id="address_neighborhood" name="address_neighborhood" value="{{ old('address_neighborhood') }}">
                            </div>
                            <div class="subscription-field span-3">
                                <label for="address_city">Cidade</label>
                                <input id="address_city" name="address_city" value="{{ old('address_city') }}">
                            </div>
                            <div class="subscription-field span-1">
                                <label for="address_state">UF</label>
                                <input class="uppercase" id="address_state" name="address_state" value="{{ old('address_state') }}" maxlength="2">
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 flex flex-wrap justify-end gap-3 border-t border-gray-100 pt-5">
                        <a class="inline-flex rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-theme-xs hover:bg-gray-50" href="{{ url('/#planos') }}">Cancelar</a>
                        <button class="inline-flex rounded-lg bg-brand-500 px-5 py-2 text-sm font-semibold text-white shadow-theme-xs hover:bg-brand-600" type="submit">Proximo</button>
                    </div>
                </form>
            </div>
        </div>
    </main>
</body>
</html>
