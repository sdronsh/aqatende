<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Usuario administrativo | AQAtende</title>
    <link rel="icon" type="image/png" href="{{ asset('logo.png') }}">
    @include('partials.pwa-meta')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        .admin-shell {
            display: grid;
            grid-template-columns: 340px minmax(0, 1fr);
            gap: 24px;
            align-items: start;
        }
        .admin-section {
            border: 1px solid #eaecf0;
            border-radius: 16px;
            padding: 20px;
            background: #fff;
        }
        .admin-grid {
            display: grid;
            grid-template-columns: repeat(12, minmax(0, 1fr));
            gap: 16px;
        }
        .admin-field {
            min-width: 0;
        }
        .admin-field label {
            display: block;
            margin-bottom: 6px;
            font-size: 13px;
            font-weight: 600;
            color: #344054;
        }
        .admin-field input {
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
        .admin-field input:focus {
            border-color: #b12ca0;
            box-shadow: 0 0 0 4px rgba(177, 44, 160, .12);
        }
        .span-6 { grid-column: span 6 / span 6; }
        .span-12 { grid-column: span 12 / span 12; }
        @media (max-width: 1024px) {
            .admin-shell {
                grid-template-columns: 1fr;
            }
        }
        @media (max-width: 720px) {
            .admin-grid {
                grid-template-columns: 1fr;
            }
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
                <a class="rounded-full border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-theme-xs hover:bg-gray-100" href="{{ route('subscriptions.billing', $plan['slug']) }}">Voltar</a>
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

            <div class="admin-shell">
                <aside class="rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-sm">
                    <div class="text-xs font-bold uppercase tracking-[0.24em] text-brand-600">Etapa 3 de 3</div>
                    <h1 class="mt-4 text-2xl font-semibold text-gray-900">Usuario administrativo</h1>
                    <div class="mt-5 rounded-xl border border-brand-100 bg-brand-50 p-4">
                        <div class="text-xs font-semibold uppercase text-brand-700">Empresa</div>
                        <div class="mt-1 text-lg font-semibold text-brand-800">{{ $pending['company_name'] ?? 'Empresa cadastrada' }}</div>
                        <div class="mt-2 text-sm text-brand-700">Licenca #{{ $pending['license_id'] }}</div>
                    </div>
                    <p class="mt-6 text-sm leading-6 text-gray-500">
                        O sistema criara a empresa no AQAtende, o perfil Administrativo com todos os acessos e vinculara esse usuario ao perfil.
                    </p>
                </aside>

                <form method="POST" action="{{ route('subscriptions.admin.store', $plan['slug']) }}" class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-sm md:p-6">
                    @csrf
                    <div class="admin-section">
                        <h2 class="mb-4 text-lg font-semibold text-gray-900">Dados de acesso</h2>
                        <div class="admin-grid">
                            <div class="admin-field span-6">
                                <label for="name">Nome do administrador</label>
                                <input id="name" name="name" value="{{ old('name') }}" required>
                            </div>
                            <div class="admin-field span-6">
                                <label for="username">Usuario</label>
                                <input id="username" name="username" value="{{ old('username') }}" required>
                            </div>
                            <div class="admin-field span-12">
                                <label for="email">E-mail</label>
                                <input id="email" type="email" name="email" value="{{ old('email') }}" required>
                            </div>
                            <div class="admin-field span-6">
                                <label for="password">Senha</label>
                                <input id="password" type="password" name="password" required>
                            </div>
                            <div class="admin-field span-6">
                                <label for="password_confirmation">Confirmar senha</label>
                                <input id="password_confirmation" type="password" name="password_confirmation" required>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 flex flex-wrap justify-end gap-3 border-t border-gray-100 pt-5">
                        <a class="inline-flex rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-theme-xs hover:bg-gray-50" href="{{ route('subscriptions.billing', $plan['slug']) }}">Voltar</a>
                        <button class="inline-flex rounded-lg bg-brand-500 px-5 py-2 text-sm font-semibold text-white shadow-theme-xs hover:bg-brand-600" type="submit">Criar acesso</button>
                    </div>
                </form>
            </div>
        </div>
    </main>
    @include('partials.pwa-install-prompt')
</body>
</html>
