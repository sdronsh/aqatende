<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Contratar {{ $plan['name'] }} | AQAtende</title>
    <link rel="icon" type="image/png" href="{{ asset('logo.png') }}">
    @include('partials.pwa-meta')
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
            border-color: #256d7f;
            box-shadow: 0 0 0 4px rgba(37, 109, 127, .14);
        }
        .subscription-step[hidden] {
            display: none;
        }
        .subscription-step-head {
            margin-bottom: 22px;
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
        }
        .subscription-eyebrow {
            margin-bottom: 8px;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: .22em;
            text-transform: uppercase;
            color: #256d7f;
        }
        .subscription-step-title {
            font-size: clamp(24px, 3vw, 34px);
            font-weight: 750;
            line-height: 1.12;
            color: #101828;
        }
        .subscription-step-copy {
            margin-top: 10px;
            max-width: 680px;
            color: #475467;
            font-size: 15px;
            line-height: 1.7;
        }
        .business-activity-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 14px;
        }
        .business-activity-option {
            position: relative;
            display: block;
            min-width: 0;
        }
        .business-activity-option input {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }
        .business-activity-card {
            display: flex;
            min-height: 150px;
            height: 100%;
            flex-direction: column;
            justify-content: space-between;
            border: 1px solid #d0d5dd;
            border-radius: 16px;
            background: #fff;
            padding: 18px;
            color: #344054;
            box-shadow: 0 1px 2px rgba(16, 24, 40, .05);
            transition: border-color .18s ease, box-shadow .18s ease, transform .18s ease, background .18s ease;
        }
        .business-activity-card svg {
            color: var(--activity-color);
            flex: 0 0 auto;
        }
        .business-activity-option input:checked + .business-activity-card {
            border-color: var(--activity-color);
            background: color-mix(in srgb, var(--activity-color) 6%, #fff);
            box-shadow: 0 0 0 4px color-mix(in srgb, var(--activity-color) 15%, transparent);
        }
        .business-activity-option:hover .business-activity-card {
            transform: translateY(-1px);
            border-color: var(--activity-color);
        }
        .business-activity-title {
            display: block;
            color: #101828;
            font-size: 15px;
            font-weight: 750;
            line-height: 1.35;
        }
        .business-activity-copy {
            margin-top: 6px;
            display: block;
            color: #667085;
            font-size: 13px;
            line-height: 1.45;
        }
        .subscription-actions {
            display: flex;
            flex-wrap: wrap;
            justify-content: flex-end;
            gap: 12px;
            border-top: 1px solid #eaecf0;
            padding-top: 20px;
            margin-top: 24px;
        }
        .subscription-btn-primary,
        .subscription-btn-secondary {
            display: inline-flex;
            min-height: 42px;
            align-items: center;
            justify-content: center;
            border-radius: 999px;
            padding: 10px 18px;
            font-size: 14px;
            font-weight: 750;
            transition: background .18s ease, border-color .18s ease, color .18s ease, transform .18s ease;
        }
        .subscription-btn-primary {
            border: 1px solid #256d7f;
            background: #256d7f;
            color: #fff;
        }
        .subscription-btn-primary:hover {
            background: #1f5b6a;
            border-color: #1f5b6a;
        }
        .subscription-btn-secondary {
            border: 1px solid #d0d5dd;
            background: #fff;
            color: #344054;
        }
        .subscription-btn-secondary:hover {
            background: #f8fafc;
            border-color: #b8c1cc;
        }
        @media (max-width: 720px) {
            .business-activity-grid {
                grid-template-columns: 1fr;
            }
            .business-activity-card {
                min-height: auto;
                gap: 18px;
            }
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
        @media (max-width: 840px) {
            .business-activity-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }
        @media (max-width: 480px) {
            .business-activity-grid {
                grid-template-columns: 1fr;
            }
        }
        @media (max-width: 720px) {
            .subscription-grid {
                grid-template-columns: 1fr;
            }
            .subscription-step-head {
                display: block;
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
                    <img class="h-12 w-auto object-contain" src="{{ asset('brand/logo-horizontal-light.png') }}" alt="AQAtende">
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
                    <div class="subscription-eyebrow">Plano selecionado</div>
                    <h1 class="mt-4 text-2xl font-semibold text-gray-900">{{ $plan['name'] }}</h1>
                    <div class="mt-4 text-4xl font-semibold text-gray-900">R$ {{ number_format($plan['amount'], 2, ',', '.') }}</div>
                    <div class="mt-1 text-sm font-medium text-gray-500">mensal</div>
                    <div class="mt-6 space-y-3 text-sm text-gray-600">
                        <div class="flex justify-between gap-4 border-b border-gray-100 pb-3">
                            <span>Profissionais</span>
                            <strong class="text-gray-800">{{ $plan['professional_limit'] ? 'Até '.$plan['professional_limit'] : 'Sem limite' }}</strong>
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
                        Esses dados serão enviados automaticamente para o sistema de licenças. Na próxima etapa você define o vencimento da assinatura.
                    </p>
                </aside>

                <form method="POST" action="{{ route('subscriptions.store', $plan['slug']) }}" class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-sm md:p-6">
                    @csrf
                    @php
                        $activityCards = [
                            'salao_barbearia' => ['#9b2a90', 'Atendimento, agenda e equipe para rotinas de beleza.'],
                            'pet_shop' => ['#a96a22', 'Serviços, retornos e acompanhamento de clientes pet.'],
                            'estetica_tatuagem' => ['#3f3f46', 'Agenda e controle para atendimentos personalizados.'],
                            'automotivo' => ['#2563eb', 'Organização de serviços, fila e histórico de atendimento.'],
                            'aulas_treinamentos' => ['#0f766e', 'Agenda para horarios, instrutores e atendimentos recorrentes.'],
                            'outros' => ['#256d7f', 'Uma configuração neutra para outros negócios de atendimento.'],
                        ];
                        $selectedBusinessActivity = old('business_activity', \App\Models\Company::defaultBusinessActivity());
                        $showDetailsStep = $errors->any() && ! $errors->has('business_activity');
                    @endphp
                    <div class="subscription-step" data-step="activity" @if($showDetailsStep) hidden @endif>
                        <div class="subscription-step-head">
                            <div>
                                <div class="subscription-eyebrow">Comece pelo ramo</div>
                                <h1 class="subscription-step-title">Escolha o tipo de negócio.</h1>
                                <p class="subscription-step-copy">
                                    Essa escolha prepara a identidade visual inicial do sistema. Depois você informa os dados da empresa e segue para a assinatura.
                                </p>
                            </div>
                        </div>

                        <div class="business-activity-grid">
                            @foreach ($businessActivities as $value => $label)
                                @php [$color, $description] = $activityCards[$value] ?? $activityCards['outros']; @endphp
                                <label class="business-activity-option" style="--activity-color: {{ $color }}">
                                    <input type="radio" name="business_activity" value="{{ $value }}" @checked($selectedBusinessActivity === $value) required>
                                    <span class="business-activity-card">
                                        <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            @switch($value)
                                                @case('salao_barbearia')
                                                    <path d="M4 18c4-1 8-5 10-10" />
                                                    <path d="M14 8l6-4" />
                                                    <path d="M7 21l4-4" />
                                                    <path d="M5 5l14 14" />
                                                    @break
                                                @case('pet_shop')
                                                    <path d="M6 19c1.4-3 3.8-5 6-5s4.6 2 6 5" />
                                                    <circle cx="6" cy="9" r="2" />
                                                    <circle cx="18" cy="9" r="2" />
                                                    <circle cx="12" cy="6" r="2" />
                                                    @break
                                                @case('estetica_tatuagem')
                                                    <path d="M4 20l6-6" />
                                                    <path d="M14 4l6 6" />
                                                    <path d="M13 5l6 6-8 8H5v-6l8-8z" />
                                                    @break
                                                @case('automotivo')
                                                    <path d="M5 16l1.5-5h11L19 16" />
                                                    <path d="M7 16h10" />
                                                    <path d="M7 19h.01" />
                                                    <path d="M17 19h.01" />
                                                    <path d="M4 16h16v4H4z" />
                                                    @break
                                                @case('aulas_treinamentos')
                                                    <path d="M4 6h16" />
                                                    <path d="M4 10h16" />
                                                    <path d="M7 14h10" />
                                                    <path d="M9 18h6" />
                                                    <path d="M8 2h8" />
                                                    @break
                                                @default
                                                    <path d="M4 7h7v7H4z" />
                                                    <path d="M13 7h7v7h-7z" />
                                                    <path d="M4 16h7v4H4z" />
                                                    <path d="M13 16h7v4h-7z" />
                                            @endswitch
                                        </svg>
                                        <span>
                                            <span class="business-activity-title">{{ $label }}</span>
                                            <span class="business-activity-copy">{{ $description }}</span>
                                        </span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                        <x-input-error class="mt-3" :messages="$errors->get('business_activity')" />

                        <div class="subscription-actions">
                            <a class="subscription-btn-secondary" href="{{ url('/#planos') }}">Voltar aos planos</a>
                            <button class="subscription-btn-primary" type="button" data-next-step>Continuar</button>
                        </div>
                    </div>

                    <div class="subscription-step" data-step="details" @unless($showDetailsStep) hidden @endunless>
                    <div class="subscription-step-head">
                        <div>
                            <div class="subscription-eyebrow">Dados da empresa</div>
                            <h1 class="subscription-step-title">Informe os dados para contratação.</h1>
                            <p class="subscription-step-copy">
                                Depois desta etapa você define o vencimento da assinatura e cadastra o usuário administrador.
                            </p>
                        </div>
                    </div>

                    <div class="subscription-section">
                        <h2 class="subscription-section-title">Dados da empresa</h2>
                        <div class="subscription-grid">
                            <div class="subscription-field span-7">
                                <label for="name">Nome da empresa</label>
                                <input id="name" name="name" value="{{ old('name') }}" required>
                            </div>
                            <div class="subscription-field span-5">
                                <label for="cnpj">CNPJ ou CPF</label>
                                <input id="cnpj" name="cnpj" value="{{ old('cnpj') }}" placeholder="00.000.000/0000-00 ou 000.000.000-00" data-mask="cpf-cnpj" required>
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

                    <div class="subscription-actions">
                        <button class="subscription-btn-secondary" type="button" data-prev-step>Voltar</button>
                        <button class="subscription-btn-primary" type="submit">Proximo</button>
                    </div>
                    </div>
                </form>
            </div>
        </div>
    </main>
    <script>
        const formatCpfCnpj = (value) => {
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

        document.querySelectorAll('[data-mask="cpf-cnpj"]').forEach((input) => {
            input.value = formatCpfCnpj(input.value);
            input.addEventListener('input', () => {
                input.value = formatCpfCnpj(input.value);
            });
        });

        const activityStep = document.querySelector('[data-step="activity"]');
        const detailsStep = document.querySelector('[data-step="details"]');
        const selectedActivity = () => document.querySelector('input[name="business_activity"]:checked');

        document.querySelector('[data-next-step]')?.addEventListener('click', () => {
            if (!selectedActivity()) {
                document.querySelector('input[name="business_activity"]')?.reportValidity();
                return;
            }

            activityStep.hidden = true;
            detailsStep.hidden = false;
            document.getElementById('name')?.focus();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });

        document.querySelector('[data-prev-step]')?.addEventListener('click', () => {
            detailsStep.hidden = true;
            activityStep.hidden = false;
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    </script>
</body>
</html>
