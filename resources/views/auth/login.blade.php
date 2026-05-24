<x-guest-layout :fullBleed="true">
    @php
        $mode = request('mode');
        if (! in_array($mode, ['company', 'master'], true)) {
            $mode = 'company';
        }
    @endphp

    <style>
        .pwa-standalone .pwa-login-only .login-marketing,
        .pwa-standalone .pwa-login-only .login-footer,
        .pwa-standalone .pwa-login-only .login-cancel-link {
            display: none !important;
        }

        .pwa-standalone .pwa-login-only .login-shell {
            align-items: center;
            justify-content: center;
            max-width: none;
            padding: 1.5rem;
        }

        .pwa-standalone .pwa-login-only .login-grid {
            display: flex;
            width: 100%;
            justify-content: center;
        }

        .pwa-standalone .pwa-login-only .login-card-section {
            width: 100%;
            justify-content: center;
        }

        .login-page {
            background:
                radial-gradient(circle at top left, rgba(37, 109, 127, .14), transparent 34%),
                linear-gradient(135deg, #f7fbfc 0%, #ffffff 48%, #eef7f9 100%);
        }

        .login-badge {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            border-radius: 999px;
            border: 1px solid #b9dce4;
            background: #eef8fa;
            padding: .25rem .75rem;
            color: #256d7f;
            font-size: .75rem;
            font-weight: 750;
            letter-spacing: .12em;
            text-transform: uppercase;
        }

        .login-card {
            border: 1px solid #d8e8ec;
            background: rgba(255, 255, 255, .94);
            box-shadow: 0 24px 70px -34px rgba(16, 74, 88, .46);
        }

        .login-logo {
            height: 72px;
            width: auto;
            max-width: 260px;
            object-fit: contain;
        }

        .login-feature-card {
            border: 1px solid #d8e8ec;
            background: rgba(255, 255, 255, .9);
        }

        .login-check {
            background: #dff3f6;
            color: #256d7f;
        }

        .login-card input:not([type="checkbox"]) {
            border-color: #d0d5dd;
        }

        .login-card input:not([type="checkbox"]):focus {
            border-color: #256d7f !important;
            box-shadow: 0 0 0 4px rgba(37, 109, 127, .14) !important;
        }

        .login-card input[type="checkbox"] {
            accent-color: #256d7f;
        }

        .login-submit {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: .75rem;
            background: #256d7f;
            padding: .5rem 1rem;
            color: #fff;
            font-size: .875rem;
            font-weight: 700;
            box-shadow: 0 10px 24px -14px rgba(37, 109, 127, .9);
            transition: background .18s ease, box-shadow .18s ease;
        }

        .login-submit:hover {
            background: #1f5b6a;
            box-shadow: 0 12px 26px -14px rgba(37, 109, 127, 1);
        }

        .login-submit:focus {
            outline: none;
            box-shadow: 0 0 0 4px rgba(37, 109, 127, .18);
        }

        @media (display-mode: standalone) {
            .pwa-login-only .login-marketing,
            .pwa-login-only .login-footer,
            .pwa-login-only .login-cancel-link {
                display: none !important;
            }

            .pwa-login-only .login-shell {
                align-items: center;
                justify-content: center;
                max-width: none;
                padding: 1.5rem;
            }

            .pwa-login-only .login-grid {
                display: flex;
                width: 100%;
                justify-content: center;
            }

            .pwa-login-only .login-card-section {
                width: 100%;
                justify-content: center;
            }
        }

        @media (max-height: 800px) {
            .login-shell { padding-top: 1.5rem; padding-bottom: 1.5rem; }
            .login-hero h1 { font-size: 2rem; }
            .login-hero p { font-size: 0.95rem; }
            .login-features { display: none; }
            .login-footer { margin-top: 1.5rem; }
            .login-card { padding: 2rem; }
            .login-logo { height: 64px; max-width: 230px; }
        }

        @media (max-width: 640px) {
            .login-shell {
                padding-left: 1rem;
                padding-right: 1rem;
                padding-top: 1.25rem;
                padding-bottom: 1.25rem;
            }

            .login-grid {
                gap: 1.5rem;
            }

            .login-card-section {
                order: -1;
            }

            .login-marketing {
                padding-bottom: 1.5rem;
            }

            .login-hero h1 {
                margin-top: 1rem;
                font-size: 1.9rem;
                line-height: 1.18;
            }

            .login-hero p {
                font-size: .95rem;
            }

            .login-card {
                border-radius: 22px;
                padding: 1.5rem;
            }

            .login-logo {
                height: 64px;
                max-width: 240px;
            }
        }
    </style>

    <script>
        if (window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true) {
            document.documentElement.classList.add('pwa-standalone');
        }
    </script>

    <div class="pwa-login-only login-page min-h-screen">
        <div class="login-shell mx-auto flex min-h-screen max-w-6xl flex-col px-6 py-8 lg:py-12">
            <div class="login-grid grid items-center gap-10 lg:grid-cols-2">
                <section class="login-marketing">
                    <div class="login-badge">
                        Plataforma AQAtende
                    </div>

                    <div class="login-hero">
                        <h1 class="mt-6 text-4xl font-semibold leading-tight text-gray-900 lg:text-5xl">
                        Gestão profissional do atendimento com eficiência, controle e confiança.
                        </h1>
                        <p class="mt-4 max-w-2xl text-base text-gray-600">
                            Centralize agenda, fila de atendimento, clientes e financeiro em um só lugar. O AQAtende entrega
                            controle operacional para profissionais e negócios que trabalham com horário marcado e encaixes.
                        </p>
                    </div>

                    <div class="login-features mt-8 grid gap-4 sm:grid-cols-3">
                        <div class="login-feature-card rounded-2xl p-4 shadow-theme-xs">
                            <h3 class="text-sm font-semibold text-gray-800">Agenda e fila</h3>
                            <p class="mt-2 text-xs text-gray-500">Controle horários marcados e atendimentos por ordem de chegada no mesmo fluxo.</p>
                        </div>
                        <div class="login-feature-card rounded-2xl p-4 shadow-theme-xs">
                            <h3 class="text-sm font-semibold text-gray-800">Equipe e comissões</h3>
                            <p class="mt-2 text-xs text-gray-500">Vincule profissionais aos serviços e calcule comissões ao finalizar atendimentos.</p>
                        </div>
                        <div class="login-feature-card rounded-2xl p-4 shadow-theme-xs">
                            <h3 class="text-sm font-semibold text-gray-800">Financeiro e relatórios</h3>
                            <p class="mt-2 text-xs text-gray-500">Receitas, despesas e indicadores para gestão estratégica da operação.</p>
                        </div>
                    </div>

                    <div class="mt-8 flex items-center gap-3 text-sm text-gray-500">
                        <span class="login-check inline-flex h-6 w-6 items-center justify-center rounded-full">✓</span>
                        Plataforma segura, pronta para operação multiempresa e gestão diária do atendimento.
                    </div>
                </section>

                <section class="login-card-section relative flex justify-center lg:justify-end">
                    <div class="login-card w-full max-w-md rounded-[28px] p-10">
                        <div class="mb-6 flex justify-center">
                            <img class="login-logo" src="{{ asset('brand/logo-horizontal-light.png') }}" alt="AQAtende" />
                        </div>

                        <form method="POST" action="{{ route('login') }}" class="space-y-4">
                            @csrf
                            <input type="hidden" name="mode" value="{{ $mode }}">

                            @if ($mode === 'company')
                                <div>
                                    <label class="mb-1 block text-sm font-medium text-gray-700" for="company_code">CNPJ ou CPF</label>
                                    <input id="company_code" class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 shadow-theme-xs focus:border-brand-300 focus:outline-none focus:ring-3 focus:ring-brand-500/10" type="text" name="company_code" value="{{ old('company_code') }}" data-mask="cnpj" autocomplete="off" autofocus placeholder="00.000.000/0000-00 ou 000.000.000-00" />
                                    <div id="company-error" class="mt-1">
                                        <x-input-error :messages="$errors->get('company_code')" />
                                    </div>
                                    <div id="company-name" class="text-xs text-gray-500 mt-1"></div>
                                </div>
                            @endif

                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700" for="username">Usuario</label>
                                <input id="username" class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 shadow-theme-xs focus:border-brand-300 focus:outline-none focus:ring-3 focus:ring-brand-500/10" type="text" name="username" value="{{ old('username') }}" required {{ $mode === 'master' ? 'autofocus' : '' }} autocomplete="username" />
                                <x-input-error :messages="$errors->get('username')" class="mt-1" />
                            </div>

                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700" for="password">Senha</label>
                                <input id="password" class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 shadow-theme-xs focus:border-brand-300 focus:outline-none focus:ring-3 focus:ring-brand-500/10" type="password" name="password" required autocomplete="current-password" />
                                <x-input-error :messages="$errors->get('password')" class="mt-1" />
                            </div>

                            <label class="flex items-center gap-2 text-sm text-gray-600">
                                <input id="remember_me" type="checkbox" class="h-4 w-4 rounded border-gray-300" name="remember">
                                Lembrar
                            </label>

                            <div class="flex items-center justify-between">
                                @if (Route::has('password.request'))
                                    <a class="text-sm text-gray-500 underline" href="{{ route('password.request') }}">
                                        Esqueceu a senha?
                                    </a>
                                @endif
                                <div class="flex items-center gap-2">
                                    <a class="login-cancel-link rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-theme-xs hover:bg-gray-50" href="{{ url('/') }}">Cancelar</a>
                                    <button class="login-submit" type="submit">
                                        Entrar
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </section>
            </div>

            <footer class="login-footer mt-8 text-xs text-gray-500">
                AQAtende • Gestão integrada para profissionais e negócios de atendimento • Todos os direitos reservados.
            </footer>
        </div>
    </div>

    @if ($mode === 'company')
        <script>
            const companyInput = document.getElementById('company_code');
            const companyName = document.getElementById('company-name');
            const loginForm = document.querySelector('form[action="{{ route('login') }}"]');
            let lookupController = null;
            let isSubmitting = false;

            loginForm?.addEventListener('submit', () => {
                isSubmitting = true;
                if (lookupController) {
                    lookupController.abort();
                    lookupController = null;
                }
            });

            companyInput?.addEventListener('input', () => {
                if (!companyInput.value.trim()) {
                    companyName.textContent = '';
                    const errorBox = document.getElementById('company-error');
                    if (errorBox) {
                        errorBox.innerHTML = '';
                        errorBox.style.display = 'none';
                    }
                }
            });

            companyInput?.addEventListener('blur', async () => {
                if (isSubmitting) return;

                const code = (companyInput.value || '').replace(/\\D/g, '').trim();
                companyName.textContent = '';
                const errorBox = document.getElementById('company-error');
                if (errorBox) {
                    errorBox.style.display = 'none';
                }

                if (!code) return;

                try {
                    if (lookupController) {
                        lookupController.abort();
                    }
                    lookupController = new AbortController();

                    const response = await fetch(`/company-lookup?code=${encodeURIComponent(code)}`, {
                        headers: { 'Accept': 'application/json' },
                        signal: lookupController.signal,
                    });

                    if (!response.ok) {
                        companyName.textContent = 'Empresa nao encontrada.';
                        return;
                    }

                    const data = await response.json();
                    if (data?.name) {
                        companyName.textContent = `Empresa: ${data.name}`;
                        if (errorBox) errorBox.innerHTML = '';
                    } else {
                        companyName.textContent = 'Empresa nao encontrada.';
                    }
                } catch (error) {
                    if (error?.name === 'AbortError') return;
                    companyName.textContent = 'Nao foi possivel validar o codigo.';
                } finally {
                    lookupController = null;
                }
            });
        </script>
    @endif
</x-guest-layout>
