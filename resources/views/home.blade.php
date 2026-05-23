<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="AQAtende: sistema para profissionais e negócios de atendimento com agenda, fila, clientes e financeiro.">
    <title>AQAtende | Gestão para Profissionais e Negócios de Atendimento</title>
    <link rel="icon" type="image/png" href="{{ asset('logo.png') }}">
    @include('partials.pwa-meta', ['themeColor' => '#256d7f'])
    @if (config('services.google_analytics.measurement_id'))
        <script async src="https://www.googletagmanager.com/gtag/js?id={{ config('services.google_analytics.measurement_id') }}"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());
            gtag('config', '{{ config('services.google_analytics.measurement_id') }}');
        </script>
    @endif
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        :root {
            --landing-primary: #256d7f;
            --landing-primary-dark: #1d3d48;
            --landing-primary-soft: #f5fbfc;
            --landing-accent: #14b8a6;
        }
        html { scroll-behavior: smooth; }
        .landing-hero {
            background:
                linear-gradient(115deg, rgba(29, 61, 72, .92), rgba(37, 109, 127, .86)),
                url('{{ asset('landing/img/mockups/devices-01.png') }}') center right / min(900px, 70vw) no-repeat;
        }
        .activity-dot { background: var(--activity-color); }
        .activity-card { border-color: color-mix(in srgb, var(--activity-color) 28%, #e5e7eb); }
        .activity-card:hover { box-shadow: 0 18px 40px -30px var(--activity-color); }
    </style>
</head>
<body class="bg-white text-gray-900">
    @php
        $activities = [
            ['salao_barbearia', 'Salao / Barbearia', 'Agenda, fila, serviços, profissionais e comissões.', '#a81d8e', 'M4 18c4-1 8-5 10-10M14 8l6-4M7 21l4-4M5 5l14 14'],
            ['pet_shop', 'Pet Shop', 'Banho, tosa, retornos, clientes e pacotes.', '#b86b16', 'M6 19c1.5-3 4-5 6-5s4.5 2 6 5M8 9a2 2 0 1 0-4 0 2 2 0 0 0 4 0M20 9a2 2 0 1 0-4 0 2 2 0 0 0 4 0M10 6a2 2 0 1 0 4 0 2 2 0 0 0-4 0'],
            ['estetica_tatuagem', 'Estetica e Tatuagem', 'Atendimentos detalhados, agenda e financeiro.', '#3f3f46', 'M4 20l6-6M14 4l6 6M13 5l6 6-8 8H5v-6l8-8z'],
            ['automotivo', 'Automotivo', 'Serviços, encaixes, ordens e controle de caixa.', '#2563eb', 'M5 16l1.5-5h11L19 16M7 16h10M7 19h.01M17 19h.01M4 16h16v4H4z'],
            ['aulas_treinamentos', 'Aulas / Treinamentos', 'Horários, instrutores, alunos e recebimentos.', '#0f9f8f', 'M4 6h16M4 10h16M7 14h10M9 18h6M6 22h12M8 2h8'],
            ['outros', 'Outros', 'Flexível para qualquer operação com atendimento.', '#256d7f', 'M4 7h7v7H4zM13 7h7v7h-7zM4 16h7v4H4zM13 16h7v4h-7z'],
        ];
    @endphp

    <header class="fixed inset-x-0 top-0 z-50 border-b border-gray-200 shadow-sm" style="background-color: rgba(255, 255, 255, .97);">
        <div class="mx-auto flex max-w-7xl items-center justify-between px-5 py-4">
            <a href="{{ url('/') }}" class="flex items-center gap-3">
                <img class="h-12 w-auto max-w-[190px] object-contain" src="{{ asset('brand/logo-horizontal-light.png') }}" alt="AQAtende">
            </a>
            <nav class="hidden items-center gap-7 text-sm font-semibold md:flex" style="color: #344054;">
                <a style="color: #344054;" href="#ramos">Ramos</a>
                <a style="color: #344054;" href="#recursos">Recursos</a>
                <a style="color: #344054;" href="#fluxo">Fluxo</a>
                <a style="color: #344054;" href="#planos">Planos</a>
                <a style="color: #344054;" href="#ajuda">Ajuda</a>
            </nav>
            <a class="rounded-full px-4 py-2 text-sm font-semibold text-white shadow-theme-xs" style="background-color: #256d7f; color: #ffffff;" href="{{ route('login', ['mode' => 'company']) }}">
                Entrar
            </a>
        </div>
    </header>

    <main>
        <section class="landing-hero min-h-screen overflow-hidden pt-24 text-white">
            <div class="mx-auto grid min-h-[calc(100vh-6rem)] max-w-7xl items-center gap-12 px-5 py-16 lg:grid-cols-[1.05fr_.95fr]">
                <div>
                    <p class="mb-7 text-xs font-bold uppercase tracking-[0.32em] text-white/70">Gestao para negocios de atendimento</p>
                    <h1 class="max-w-4xl text-4xl font-semibold leading-tight md:text-6xl">
                        Gestão simples para negócios de atendimento.
                    </h1>
                    <p class="mt-6 max-w-2xl text-lg leading-8 text-white/80">
                        Organize agenda, fila, clientes, serviços, profissionais e financeiro em uma única plataforma.
                    </p>
                    <div class="mt-9 flex flex-wrap gap-3">
                        <a class="rounded-full bg-white px-7 py-3 text-sm font-bold shadow-theme-lg" style="color: #1d3d48;" href="#planos">
                            Ver planos
                        </a>
                        <a class="rounded-full border border-white/35 px-7 py-3 text-sm font-bold text-white hover:bg-white/10" href="#ramos">
                            Ver ramos atendidos
                        </a>
                    </div>
                    <div class="mt-10 grid max-w-2xl gap-4 sm:grid-cols-3">
                        @foreach ([['Agenda', 'horários e encaixes'], ['Fila', 'ordem de chegada'], ['Financeiro', 'caixa e comissões']] as [$title, $text])
                            <div class="border-l border-white/25 pl-4">
                                <div class="text-2xl font-semibold">{{ $title }}</div>
                                <div class="mt-1 text-sm text-white/65">{{ $text }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="relative">
                    <div class="rounded-[2rem] border border-white/15 bg-white/10 p-4 shadow-[0_35px_100px_-45px_rgba(0,0,0,.75)] backdrop-blur sm:p-5">
                        <div class="rounded-[1.5rem] bg-white p-5 text-gray-900 sm:p-6">
                            <div class="flex items-center justify-between gap-4">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-[0.22em] text-[#256d7f]">Hoje</p>
                                    <h2 class="mt-1 text-2xl font-semibold">Painel operacional</h2>
                                </div>
                                <img class="h-14 w-14 object-contain" src="{{ asset('logo.png') }}" alt="AQAtende">
                            </div>
                            <div class="mt-7 grid gap-3 sm:grid-cols-2">
                                @foreach ([['Agenda', '18 atendimentos', '#256d7f'], ['Fila', '4 aguardando', '#14b8a6'], ['Recebimentos', 'R$ 1.240', '#0f9f8f'], ['Comissões', 'R$ 386', '#438b9d']] as [$title, $value, $color])
                                    <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                                        <div class="flex items-center gap-2">
                                            <span class="h-2.5 w-2.5 rounded-full" style="background: {{ $color }}"></span>
                                            <span class="text-xs font-medium text-gray-500">{{ $title }}</span>
                                        </div>
                                        <div class="mt-2 text-xl font-semibold text-gray-900">{{ $value }}</div>
                                    </div>
                                @endforeach
                            </div>
                            <div class="mt-5 rounded-xl border border-gray-200 p-4">
                                <div class="flex flex-wrap items-center justify-between gap-3">
                                    <div>
                                        <div class="text-sm font-semibold text-gray-900">Próximo atendimento</div>
                                        <div class="mt-1 text-xs text-gray-500">Cliente, serviço, profissional e cobrança em um só fluxo.</div>
                                    </div>
                                    <span class="rounded-full px-3 py-1 text-xs font-semibold" style="background-color: #256d7f; color: #ffffff;">Confirmado</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section id="ramos" class="px-5 py-20 md:py-28">
            <div class="mx-auto max-w-7xl">
                <div class="mx-auto max-w-3xl text-center">
                    <p class="text-xs font-bold uppercase tracking-[0.3em] text-[#256d7f]">Ramos atendidos</p>
                    <h2 class="mt-5 text-3xl font-semibold md:text-4xl">Um sistema para diferentes negócios de atendimento.</h2>
                    <p class="mt-5 text-lg leading-8 text-gray-600">
                        Use o AQAtende em vários ramos, mantendo agenda, fila, clientes, profissionais e financeiro em um só lugar.
                    </p>
                </div>
                <div class="mt-12 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    @foreach ($activities as [$value, $label, $text, $color, $icon])
                        <div class="activity-card rounded-2xl border bg-white p-6 shadow-theme-xs transition" style="--activity-color: {{ $color }}">
                            <div class="flex items-start gap-4">
                                <div class="activity-dot flex h-11 w-11 shrink-0 items-center justify-center rounded-xl text-white">
                                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="{{ $icon }}" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900">{{ $label }}</h3>
                                    <p class="mt-2 text-sm leading-6 text-gray-600">{{ $text }}</p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        <section id="recursos" class="bg-[#f5fbfc] px-5 py-20 md:py-28">
            <div class="mx-auto max-w-7xl">
                <div class="grid items-center gap-12 lg:grid-cols-[.9fr_1.1fr]">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.3em] text-[#256d7f]">Recursos principais</p>
                        <h2 class="mt-5 text-3xl font-semibold leading-tight md:text-4xl">Mais controle para atender melhor todos os dias.</h2>
                        <p class="mt-5 text-lg leading-8 text-gray-600">
                            Centralize os atendimentos, acompanhe a equipe e mantenha o financeiro organizado do agendamento ao pagamento.
                        </p>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        @foreach ([
                            ['Agenda e horários', 'Atendimentos marcados, recorrências e disponibilidade por profissional.'],
                            ['Fila de atendimento', 'Controle de chegada, encaixes e execução por ordem.'],
                            ['Clientes e histórico', 'Cadastro centralizado para relacionamento e retorno.'],
                            ['Financeiro integrado', 'Contas a receber, contas a pagar, caixa e comissões.'],
                        ] as [$title, $text])
                            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-xs">
                                <h3 class="text-sm font-semibold text-gray-900">{{ $title }}</h3>
                                <p class="mt-2 text-sm leading-6 text-gray-600">{{ $text }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>

        <section id="fluxo" class="px-5 py-20 md:py-28">
            <div class="mx-auto max-w-7xl">
                <div class="mx-auto max-w-3xl text-center">
                    <p class="text-xs font-bold uppercase tracking-[0.3em] text-[#256d7f]">Fluxo diario</p>
                    <h2 class="mt-5 text-3xl font-semibold md:text-4xl">Do primeiro contato ao fechamento do caixa.</h2>
                </div>
                <div class="mt-14 grid gap-5 md:grid-cols-4">
                    @foreach ([
                        ['1', 'Cliente chega', 'Cadastre ou encontre o cliente e coloque na agenda ou na fila.'],
                        ['2', 'Profissional atende', 'O sistema mostra serviços, horários e responsáveis disponíveis.'],
                        ['3', 'Serviço finaliza', 'Registre pagamento, valor ajustado e método usado.'],
                        ['4', 'Financeiro atualiza', 'Comissões, recebimentos e relatórios ficam prontos para consulta.'],
                    ] as [$number, $title, $text])
                        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-xs">
                            <div class="flex h-10 w-10 items-center justify-center rounded-full text-sm font-bold" style="background-color: #256d7f; color: #ffffff;">{{ $number }}</div>
                            <h3 class="mt-6 text-lg font-semibold">{{ $title }}</h3>
                            <p class="mt-3 text-sm leading-6 text-gray-500">{{ $text }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        <section id="planos" class="bg-gray-50 px-5 py-20 md:py-28">
            <div class="mx-auto max-w-7xl">
                <div class="mx-auto max-w-3xl text-center">
                    <p class="text-xs font-bold uppercase tracking-[0.3em] text-[#256d7f]">Planos</p>
                    <h2 class="mt-5 text-3xl font-semibold md:text-4xl">Escolha o plano e informe o ramo no pré-cadastro.</h2>
                </div>
                <div class="mx-auto mt-8 grid max-w-5xl gap-3 md:grid-cols-3">
                    <div class="rounded-2xl border border-[#cfe7ed] bg-white px-5 py-4 text-center">
                        <div class="text-sm font-semibold text-[#1d3d48]">Implantação gratuita</div>
                        <p class="mt-1 text-xs leading-5 text-gray-600">Comece sem custo de ativação.</p>
                    </div>
                    <div class="rounded-2xl border border-[#cfe7ed] bg-white px-5 py-4 text-center">
                        <div class="text-sm font-semibold text-[#1d3d48]">7 dias grátis</div>
                        <p class="mt-1 text-xs leading-5 text-gray-600">Teste o fluxo antes de contratar.</p>
                    </div>
                    <div class="rounded-2xl border border-[#cfe7ed] bg-white px-5 py-4 text-center">
                        <div class="text-sm font-semibold text-[#1d3d48]">Treinamento grátis</div>
                        <p class="mt-1 text-xs leading-5 text-gray-600">Apoiamos sua equipe nos primeiros passos.</p>
                    </div>
                </div>
                <div class="mt-14 grid gap-6 md:grid-cols-3">
                    @foreach ([
                        ['essencial', 'Essencial', 'R$ 19,90', 'mensal para ate 5 profissionais', ['Cadastro de clientes', 'Agenda', 'Fila de atendimento', 'Comissões', 'Contas a pagar e receber', 'Até 5 profissionais.']],
                        ['anual', 'Anual', 'R$ 199,90', 'anual para ate 10 profissionais', ['Todos os benefícios do Essencial', 'Melhor custo anual', 'Até 10 profissionais.']],
                        ['plus', 'Plano Plus', 'R$ 59,90', 'mensal sem limite de profissionais', ['Todos os benefícios do Essencial', 'Sem limite de profissionais', 'Para operações maiores.']],
                    ] as [$slug, $title, $price, $priceNote, $items])
                        <div class="rounded-2xl border border-gray-200 bg-white p-8 text-center shadow-theme-xs">
                            <h3 class="text-xl font-semibold text-[#1d3d48]">{{ $title }}</h3>
                            <div class="mt-4 text-4xl font-semibold text-gray-900">{{ $price }}</div>
                            <div class="mt-1 text-sm font-medium text-gray-500">{{ $priceNote }}</div>
                            <ul class="mt-6 min-h-40 space-y-2 text-left text-sm leading-6 text-gray-600">
                                @foreach ($items as $item)
                                    <li class="flex gap-2">
                                        <span class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-[#256d7f]"></span>
                                        <span>{{ $item }}</span>
                                    </li>
                                @endforeach
                            </ul>
                            <a
                                class="mt-8 inline-flex rounded-full px-6 py-3 text-sm font-semibold"
                                style="background-color: #256d7f; color: #ffffff;"
                                href="{{ route('subscriptions.create', $slug) }}"
                                data-ga-plan-click
                                data-plan-slug="{{ $slug }}"
                                data-plan-name="{{ $title }}"
                            >
                                Contratar
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="px-5 py-16 md:py-20">
            <div class="mx-auto grid max-w-7xl items-center gap-8 rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-xs md:p-8 lg:grid-cols-[1.2fr_.8fr]">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.3em] text-[#256d7f]">Integração WhatsApp</p>
                    <h2 class="mt-4 text-2xl font-semibold leading-tight text-gray-900 md:text-3xl">
                        Atendimento automático, agendamento por mensagem e IA para interagir com seus clientes.
                    </h2>
                    <p class="mt-4 max-w-3xl text-base leading-7 text-gray-600">
                        Contrate a integração com WhatsApp para receber dúvidas, automatizar respostas e facilitar agendamentos dentro do fluxo do AQAtende.
                    </p>
                </div>
                <div class="rounded-2xl border border-[#cfe7ed] bg-[#f5fbfc] p-6 text-center">
                    <div class="text-sm font-semibold uppercase tracking-[0.2em] text-[#256d7f]">Adicional ao plano</div>
                    <div class="mt-4 text-4xl font-semibold text-[#1d3d48]">R$ 19,90</div>
                    <div class="mt-1 text-sm font-medium text-[#256d7f]">por mês</div>
                    <a
                        class="mt-6 inline-flex rounded-full px-6 py-3 text-sm font-semibold"
                        style="background-color: #256d7f; color: #ffffff;"
                        href="https://wa.me/5531993723008"
                        target="_blank"
                        rel="noopener"
                        data-ga-whatsapp-click
                        data-contact-context="whatsapp_addon"
                    >
                        Entre em contato
                    </a>
                    <div class="mt-3 text-sm font-medium text-[#1d3d48]">(31) 99372-3008</div>
                </div>
            </div>
        </section>

        <section id="ajuda" class="bg-[#f5fbfc] px-5 py-20 md:py-28">
            <div class="mx-auto max-w-7xl">
                <div class="grid gap-12 lg:grid-cols-[.85fr_1.15fr]">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.3em] text-[#256d7f]">Ajuda e primeiros passos</p>
                        <h2 class="mt-5 text-3xl font-semibold leading-tight md:text-4xl">Comece pela rotina que sua empresa já usa.</h2>
                        <p class="mt-5 text-lg leading-8 text-gray-600">
                            Configure serviços, profissionais, agenda e financeiro. Depois, acompanhe a operação do dia em poucos cliques.
                        </p>
                    </div>
                    <div class="divide-y divide-gray-200 rounded-2xl border border-gray-200 bg-white shadow-theme-xs">
                        @foreach ([
                            ['Como crio um agendamento?', 'Acesse Agenda ou Agendamentos, escolha cliente, serviço, profissional, unidade, data e horário.'],
                            ['Quando uso fila e quando uso agenda?', 'Use agenda para horários marcados. Use fila para atendimento por ordem de chegada e encaixes.'],
                            ['Como o financeiro é gerado?', 'Ao criar ou finalizar atendimentos, o AQAtende cria contas a receber e movimentações relacionadas.'],
                            ['O visual muda por ramo?', 'Sim. Depois que o ramo é definido na empresa, o sistema aplica a paleta correspondente ao login daquela empresa.'],
                        ] as [$question, $answer])
                            <details class="group p-5 open:bg-white">
                                <summary class="flex cursor-pointer list-none items-center justify-between gap-4 text-sm font-semibold text-gray-900">
                                    <span>{{ $question }}</span>
                                    <span class="text-lg text-[#256d7f] group-open:rotate-45">+</span>
                                </summary>
                                <p class="mt-3 text-sm leading-6 text-gray-600">{{ $answer }}</p>
                            </details>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>

        <section id="contato" class="px-5 py-24 text-white" style="background-color: #1d3d48;">
            <div class="mx-auto max-w-3xl text-center">
                <p class="text-xs font-bold uppercase tracking-[0.3em] text-white/65">Comece agora</p>
                <h2 class="mt-5 text-3xl font-semibold md:text-4xl">Escolha um plano e configure o AQAtende para o seu ramo.</h2>
                <p class="mt-5 text-lg leading-8 text-white/75">O pré-cadastro já pergunta o segmento para deixar a empresa pronta para uma experiência mais adequada.</p>
                <div class="mt-9 flex justify-center gap-3">
                    <a class="rounded-full bg-white px-7 py-3 text-sm font-bold" style="color: #1d3d48;" href="#planos">Ver planos</a>
                </div>
            </div>
        </section>
    </main>

    <footer class="px-5 py-10 text-white" style="background-color: #102a33;">
        <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-5">
            <div class="flex items-center gap-3">
                <img class="h-16 w-auto max-w-[260px] object-contain opacity-90" src="{{ asset('brand/logo-horizontal-footer.png') }}" alt="AQAtende">
            </div>
            <div class="text-sm text-white/55">Agenda · Fila · Clientes · Financeiro</div>
        </div>
    </footer>
    @if (config('services.google_analytics.measurement_id'))
        <script>
            document.querySelectorAll('[data-ga-plan-click]').forEach((link) => {
                link.addEventListener('click', () => {
                    if (typeof gtag !== 'function') return;
                    gtag('event', 'select_plan', {
                        plan_slug: link.dataset.planSlug,
                        plan_name: link.dataset.planName,
                        link_url: link.href,
                    });
                });
            });

            document.querySelectorAll('[data-ga-whatsapp-click]').forEach((link) => {
                link.addEventListener('click', () => {
                    if (typeof gtag !== 'function') return;
                    gtag('event', 'contact_whatsapp', {
                        contact_context: link.dataset.contactContext,
                        link_url: link.href,
                    });
                });
            });
        </script>
    @endif
</body>
</html>
