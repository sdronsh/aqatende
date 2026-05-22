<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Buscar horarios | AQAtende</title>
    <link rel="icon" type="image/png" href="{{ asset('logo.png') }}">
    @include('partials.pwa-meta')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 text-gray-900">
    <header class="bg-[#26043a] text-white">
        <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-4 px-5 py-5">
            <a href="{{ url('/') }}" class="flex items-center gap-3">
                <img class="h-12 w-12 object-contain" src="{{ asset('logo.png') }}" alt="AQAtende">
                <div>
                    <div class="text-sm font-semibold uppercase tracking-[0.24em]">AQAtende</div>
                    <div class="text-xs text-white/60">Busca de horarios</div>
                </div>
            </a>
            <div class="flex flex-wrap items-center gap-2 text-sm">
                <a class="rounded-full bg-white px-4 py-2 font-semibold text-brand-800 hover:bg-brand-50" href="{{ route('login', ['mode' => 'company']) }}">Entrar como empresa</a>
            </div>
        </div>
    </header>

    <main>
        <section class="bg-gradient-to-r from-brand-950 via-brand-800 to-brand-500 px-5 py-14 text-white">
            <div class="mx-auto max-w-7xl">
                <p class="text-xs font-bold uppercase tracking-[0.3em] text-white/60">AQAtende</p>
                <h1 class="mt-4 text-3xl font-semibold md:text-5xl">Encontre profissionais, servicos e unidades.</h1>
                <p class="mt-4 max-w-2xl text-base leading-7 text-white/72">A busca publica acompanha a nova experiencia da landing e prepara o fluxo para agenda, fila e atendimentos.</p>

                <form method="GET" action="{{ route('search') }}" class="mt-8 grid gap-3 rounded-2xl border border-white/15 bg-white/10 p-4 backdrop-blur md:grid-cols-[1.3fr_1fr_auto]">
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-[0.2em] text-white/65">Servico ou profissional</label>
                        <input name="q" value="{{ $term }}" class="mt-2 w-full rounded-xl border border-white/20 bg-white px-4 py-3 text-sm text-gray-800 placeholder:text-gray-400 focus:border-brand-200 focus:outline-none focus:ring-4 focus:ring-white/20" placeholder="Ex: Corte, manicure, Ana" />
                    </div>
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-[0.2em] text-white/65">Cidade ou bairro</label>
                        <input name="location" value="{{ $location }}" class="mt-2 w-full rounded-xl border border-white/20 bg-white px-4 py-3 text-sm text-gray-800 placeholder:text-gray-400 focus:border-brand-200 focus:outline-none focus:ring-4 focus:ring-white/20" placeholder="Belo Horizonte, Savassi" />
                    </div>
                    <div class="flex items-end">
                        <button class="w-full rounded-xl bg-white px-6 py-3 text-sm font-bold text-brand-800 shadow-theme-lg hover:bg-brand-50">
                            Buscar horarios
                        </button>
                    </div>
                </form>
            </div>
        </section>

        <section class="px-5 py-12">
            <div class="mx-auto max-w-7xl">
                @if ($term === '' && $location === '')
                    <div class="rounded-2xl border border-gray-200 bg-white p-8 text-sm text-gray-600 shadow-theme-xs">
                        Digite um servico, profissional ou local para iniciar a busca.
                    </div>
                @else
                    <div class="grid gap-8 lg:grid-cols-[1.15fr_.85fr]">
                        <section class="rounded-3xl border border-gray-200 bg-white p-6 shadow-theme-xs">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-xs font-bold uppercase tracking-[0.24em] text-brand-600">Profissionais</p>
                                    <h2 class="mt-2 text-xl font-semibold text-gray-900">Resultados encontrados</h2>
                                </div>
                                <span class="rounded-full bg-brand-50 px-3 py-1 text-xs font-semibold text-brand-700">{{ $professionals->count() }} encontrados</span>
                            </div>
                            <div class="mt-6 space-y-5">
                                @forelse ($professionals as $professional)
                                    @php
                                        $unit = $professional->units->first();
                                        $specialties = $professional->specialties->pluck('name')->join(', ');
                                        $services = $professional->services->pluck('name')->take(3)->join(', ');
                                    @endphp
                                    <div class="rounded-2xl border border-gray-200 bg-white p-5 transition hover:border-brand-200 hover:shadow-theme-sm">
                                        <div class="flex flex-wrap items-start justify-between gap-4">
                                            <div class="flex items-start gap-4">
                                                <div class="flex h-16 w-16 items-center justify-center rounded-full bg-brand-50 text-lg font-semibold text-brand-700">
                                                    {{ mb_substr($professional->display_name, 0, 1) }}
                                                </div>
                                                <div>
                                                    <div class="text-base font-semibold text-gray-900">{{ $professional->display_name }}</div>
                                                    <div class="mt-1 text-sm text-gray-500">{{ $services ?: ($specialties ?: 'Servicos nao informados') }}</div>
                                                    @if ($unit)
                                                        <div class="mt-3 text-xs text-gray-500">{{ $unit->address_line1 }} · {{ $unit->city }} / {{ $unit->state }}</div>
                                                    @endif
                                                </div>
                                            </div>
                                            <a class="rounded-full bg-brand-500 px-4 py-2 text-xs font-semibold text-white hover:bg-brand-600" href="{{ route('login', ['mode' => 'company']) }}">Ver agenda</a>
                                        </div>
                                    </div>
                                @empty
                                    <div class="rounded-2xl border border-gray-200 bg-gray-50 p-5 text-sm text-gray-500">
                                        Nenhum profissional encontrado.
                                    </div>
                                @endforelse
                            </div>
                        </section>

                        <section class="rounded-3xl border border-gray-200 bg-white p-6 shadow-theme-xs">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-xs font-bold uppercase tracking-[0.24em] text-brand-600">Unidades</p>
                                    <h2 class="mt-2 text-xl font-semibold text-gray-900">Empresas e negócios de atendimento</h2>
                                </div>
                                <span class="rounded-full bg-brand-50 px-3 py-1 text-xs font-semibold text-brand-700">{{ $clinics->count() }} encontradas</span>
                            </div>
                            <div class="mt-6 space-y-4">
                                @forelse ($clinics as $clinic)
                                    @php $unit = $clinic->units->first(); @endphp
                                    <div class="rounded-2xl border border-gray-200 bg-gray-50 p-5">
                                        <div class="text-sm font-semibold text-gray-900">{{ $clinic->name }}</div>
                                        <div class="mt-1 text-xs text-gray-500">{{ $clinic->trade_name ?: 'Negócio de atendimento' }}</div>
                                        @if ($unit)
                                            <div class="mt-3 text-xs text-gray-500">{{ $unit->city }} / {{ $unit->state }}</div>
                                        @endif
                                    </div>
                                @empty
                                    <div class="rounded-2xl border border-gray-200 bg-gray-50 p-5 text-sm text-gray-500">
                                        Nenhuma unidade encontrada.
                                    </div>
                                @endforelse
                            </div>
                        </section>
                    </div>
                @endif
            </div>
        </section>
    </main>

    <footer class="bg-[#180225] px-5 py-8 text-white">
        <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-4">
            <div class="text-sm text-white/60">AQAtende · Sistema para profissionais e negócios de atendimento</div>
            <a class="text-sm font-semibold text-white" href="{{ url('/') }}">Voltar para a home</a>
        </div>
    </footer>
</body>
</html>
