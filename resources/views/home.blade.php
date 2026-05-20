<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="AQAtende: sistema de atendimento para saloes com agenda, fila, profissionais, clientes e financeiro.">
    <title>AQAtende | Sistema de Atendimento para Saloes</title>
    <link rel="icon" type="image/png" href="{{ asset('logo.png') }}">
    @include('partials.pwa-meta')
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
        html { scroll-behavior: smooth; }
        .landing-hero {
            background:
                radial-gradient(circle at 18% 15%, rgba(255, 255, 255, .22), transparent 28%),
                radial-gradient(circle at 82% 72%, rgba(255, 255, 255, .14), transparent 30%),
                linear-gradient(120deg, #26043a 0%, #65116f 50%, #c63aa6 100%);
        }
        .landing-square {
            position: relative;
        }
        .landing-square::before,
        .landing-square::after {
            content: "";
            position: absolute;
            border: 2px solid rgba(255, 255, 255, .26);
            pointer-events: none;
        }
        .landing-square::before {
            inset: -34px auto auto -28px;
            width: 92px;
            height: 92px;
        }
        .landing-square::after {
            right: 18px;
            bottom: -34px;
            width: 118px;
            height: 118px;
        }
        .landing-phone-shadow {
            filter: drop-shadow(0 34px 45px rgba(38, 4, 58, .32));
        }
    </style>
</head>
<body class="bg-white text-gray-900">
    <header class="fixed inset-x-0 top-0 z-50 border-b border-white/10 bg-[#26043a]/70 backdrop-blur">
        <div class="mx-auto flex max-w-7xl items-center justify-between px-5 py-4">
            <a href="{{ url('/') }}" class="flex items-center gap-3">
                <img class="h-12 w-12 object-contain" src="{{ asset('logo.png') }}" alt="AQAtende">
                <span class="text-sm font-semibold uppercase tracking-[0.24em] text-white">AQAtende</span>
            </a>
            <nav class="hidden items-center gap-7 text-sm font-medium text-white/80 md:flex">
                <a class="hover:text-white" href="#recursos">Recursos</a>
                <a class="hover:text-white" href="#fluxo">Fluxo</a>
                <a class="hover:text-white" href="#planos">Planos</a>
                <a class="hover:text-white" href="#ajuda">Ajuda</a>
                <a class="hover:text-white" href="#contato">Contato</a>
            </nav>
            <div class="flex items-center gap-2 text-sm">
                <a class="rounded-full bg-white px-4 py-2 font-semibold text-brand-800 shadow-theme-xs hover:bg-brand-50" href="{{ route('login', ['mode' => 'company']) }}">Entrar</a>
            </div>
        </div>
    </header>

    <main>
        <section class="landing-hero min-h-screen overflow-hidden pt-24 text-white">
            <div class="mx-auto grid min-h-[calc(100vh-6rem)] max-w-7xl items-center gap-12 px-5 py-16 lg:grid-cols-[1.1fr_.9fr]">
                <div class="landing-square">
                    <p class="mb-7 text-xs font-bold uppercase tracking-[0.32em] text-white/70">Sistema de atendimento para saloes</p>
                    <h1 class="max-w-3xl text-4xl font-semibold leading-tight md:text-6xl">
                        Agenda, fila e financeiro no mesmo ritmo do seu salao.
                    </h1>
                    <p class="mt-6 max-w-2xl text-lg leading-8 text-white/78">
                        O AQAtende organiza atendimentos marcados, encaixes, profissionais, clientes, comissoes e caixa em uma experiencia simples para operar todos os dias.
                    </p>
                    <div class="mt-9 flex flex-wrap gap-3">
                        <a class="rounded-full bg-white px-7 py-3 text-sm font-bold text-brand-800 shadow-theme-lg hover:bg-brand-50" href="{{ route('login', ['mode' => 'company']) }}">
                            Acessar minha empresa
                        </a>
                        <a class="rounded-full border border-white/35 px-7 py-3 text-sm font-bold text-white hover:bg-white/10" href="#recursos">
                            Conhecer recursos
                        </a>
                    </div>
                    <div class="mt-10 grid max-w-2xl gap-4 sm:grid-cols-3">
                        <div class="border-l border-white/25 pl-4">
                            <div class="text-2xl font-semibold">Fila</div>
                            <div class="mt-1 text-sm text-white/65">encaixes sem conflito</div>
                        </div>
                        <div class="border-l border-white/25 pl-4">
                            <div class="text-2xl font-semibold">Agenda</div>
                            <div class="mt-1 text-sm text-white/65">por dia e profissional</div>
                        </div>
                        <div class="border-l border-white/25 pl-4">
                            <div class="text-2xl font-semibold">Caixa</div>
                            <div class="mt-1 text-sm text-white/65">comissao automatica</div>
                        </div>
                    </div>
                </div>

                <div class="relative">
                    <div class="absolute -left-10 top-10 h-48 w-48 rounded-full bg-white/10 blur-3xl"></div>
                    <div class="relative rounded-[2rem] border border-white/15 bg-white/10 p-5 shadow-[0_35px_100px_-45px_rgba(0,0,0,.75)] backdrop-blur">
                        <div class="rounded-[1.5rem] bg-white p-6 text-gray-900">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-[0.22em] text-brand-600">Hoje</p>
                                    <h2 class="mt-1 text-2xl font-semibold">Painel do atendimento</h2>
                                </div>
                                <img class="h-16 w-16 object-contain" src="{{ asset('logo.png') }}" alt="AQAtende">
                            </div>
                            <div class="mt-7 space-y-3">
                                <div class="rounded-xl border border-gray-200 bg-brand-50 p-4">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <div class="text-sm font-semibold text-gray-900">Camila Duarte</div>
                                            <div class="text-xs text-gray-500">Corte feminino · 17:30</div>
                                        </div>
                                        <span class="rounded-full bg-brand-500 px-3 py-1 text-xs font-semibold text-white">Agendado</span>
                                    </div>
                                </div>
                                <div class="rounded-xl border border-gray-200 p-4">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <div class="text-sm font-semibold text-gray-900">Renata Alves</div>
                                            <div class="text-xs text-gray-500">Manicure · fila</div>
                                        </div>
                                        <span class="rounded-full bg-emerald-500 px-3 py-1 text-xs font-semibold text-white">Assumir</span>
                                    </div>
                                </div>
                                <div class="grid gap-3 pt-2 sm:grid-cols-2">
                                    <div class="rounded-xl bg-gray-50 p-4">
                                        <div class="text-xs text-gray-500">Entradas</div>
                                        <div class="mt-1 text-2xl font-semibold text-gray-900">R$ 840</div>
                                    </div>
                                    <div class="rounded-xl bg-gray-50 p-4">
                                        <div class="text-xs text-gray-500">Comissoes</div>
                                        <div class="mt-1 text-2xl font-semibold text-gray-900">R$ 286</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <img class="landing-phone-shadow mx-auto mt-8 hidden max-h-[420px] object-contain md:block" src="{{ asset('landing/img/mockups/iphone-03.png') }}" alt="Mockup de app">
                </div>
            </div>
        </section>

        <section id="recursos" class="px-5 py-20 md:py-28">
            <div class="mx-auto max-w-7xl">
                <div class="grid items-center gap-14 lg:grid-cols-2">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.3em] text-brand-600">Operacao hibrida</p>
                        <h2 class="mt-5 text-3xl font-semibold leading-tight md:text-4xl">Atenda por horario marcado e por ordem de chegada.</h2>
                        <p class="mt-5 text-lg leading-8 text-gray-600">
                            Saloes alternam agenda, encaixes, atrasos e profissionais livres. O AQAtende foi pensado para esse fluxo: o cliente entra na fila, o profissional assume, o servico termina e o financeiro nasce automaticamente.
                        </p>
                        <div class="mt-8 grid gap-4 sm:grid-cols-2">
                            <div class="rounded-xl border border-gray-200 p-5">
                                <div class="text-sm font-semibold text-gray-900">Profissionais e servicos</div>
                                <p class="mt-2 text-sm leading-6 text-gray-500">Vincule quem atende cada servico e aplique precos/duracoes especificas.</p>
                            </div>
                            <div class="rounded-xl border border-gray-200 p-5">
                                <div class="text-sm font-semibold text-gray-900">Comissao flexivel</div>
                                <p class="mt-2 text-sm leading-6 text-gray-500">Percentual ou valor fixo por profissional, com prioridade por servico.</p>
                            </div>
                        </div>
                    </div>
                    <div class="text-center">
                        <img class="mx-auto max-h-[560px] object-contain" src="{{ asset('landing/img/mockups/iphone-02.png') }}" alt="Controle no celular">
                    </div>
                </div>
            </div>
        </section>

        <section id="fluxo" class="bg-gray-50 px-5 py-20 md:py-28">
            <div class="mx-auto max-w-7xl">
                <div class="mx-auto max-w-3xl text-center">
                    <p class="text-xs font-bold uppercase tracking-[0.3em] text-brand-600">Fluxo diario</p>
                    <h2 class="mt-5 text-3xl font-semibold md:text-4xl">Do cliente na recepcao ao fechamento do caixa.</h2>
                </div>
                <div class="mt-14 grid gap-5 md:grid-cols-4">
                    @foreach ([
                        ['1', 'Cliente chega', 'Cadastre ou encontre o cliente e coloque na agenda ou na fila.'],
                        ['2', 'Profissional assume', 'O sistema sugere profissionais ativos que atendem o servico.'],
                        ['3', 'Servico finaliza', 'Registre pagamento, valor ajustado e metodo usado.'],
                        ['4', 'Financeiro nasce', 'Comissao e valor do salao ficam prontos para relatorios.'],
                    ] as [$number, $title, $text])
                        <div class="rounded-xl bg-white p-6 shadow-theme-xs">
                            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-brand-500 text-sm font-bold text-white">{{ $number }}</div>
                            <h3 class="mt-6 text-lg font-semibold">{{ $title }}</h3>
                            <p class="mt-3 text-sm leading-6 text-gray-500">{{ $text }}</p>
                        </div>
                    @endforeach
                </div>
                <div class="mt-14">
                    <img class="mx-auto max-h-[420px] object-contain" src="{{ asset('landing/img/mockups/devices-01.png') }}" alt="AQAtende em dispositivos">
                </div>
            </div>
        </section>

        <section id="planos" class="px-5 py-20 md:py-28">
            <div class="mx-auto max-w-7xl">
                <div class="mx-auto max-w-3xl text-center">
                    <p class="text-xs font-bold uppercase tracking-[0.3em] text-brand-600">Planos</p>
                    <h2 class="mt-5 text-3xl font-semibold md:text-4xl">Preparado para crescer com os seus atendimentos.</h2>
                </div>
                <div class="mx-auto mt-8 grid max-w-5xl gap-3 md:grid-cols-3">
                    <div class="rounded-2xl border border-brand-100 bg-brand-50 px-5 py-4 text-center">
                        <div class="text-sm font-semibold text-brand-800">Implantação gratuita</div>
                        <p class="mt-1 text-xs leading-5 text-brand-700">Comece sem custo de ativação.</p>
                    </div>
                    <div class="rounded-2xl border border-brand-100 bg-brand-50 px-5 py-4 text-center">
                        <div class="text-sm font-semibold text-brand-800">7 dias grátis</div>
                        <p class="mt-1 text-xs leading-5 text-brand-700">Teste o fluxo antes de contratar.</p>
                    </div>
                    <div class="rounded-2xl border border-brand-100 bg-brand-50 px-5 py-4 text-center">
                        <div class="text-sm font-semibold text-brand-800">Treinamento e configuração grátis</div>
                        <p class="mt-1 text-xs leading-5 text-brand-700">Apoiamos sua equipe nos primeiros passos.</p>
                    </div>
                </div>
                <div class="mt-14 grid gap-6 md:grid-cols-3">
                    @foreach ([
                        ['essencial', 'Essencial', 'R$ 19,90', 'mensal para ate 5 profissionais', ['Cadastro de Clientes', 'Agenda', 'Fila de Atendimento', 'Comissões de Atendimento', 'Contas a Pagar', 'Contas a Receber', 'Até 5 profissionais.']],
                        ['anual', 'Anual', 'R$ 199,90', 'anual para ate 10 profissionais', ['Todos os benefícios do pacote Essencial', 'Para uma maior quantidade de profissionais', 'Até 10 profissionais.']],
                        ['plus', 'Plano Plus', 'R$ 59,90', 'mensal sem limite de profissionais', ['Todos os benefícios do pacote Essencial', 'Sem limites de profissionais.']],
                    ] as [$slug, $title, $price, $priceNote, $items])
                        <div class="rounded-2xl border border-gray-200 bg-white p-8 text-center shadow-theme-xs">
                            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-brand-50 text-brand-600">
                                <span class="text-xl">✓</span>
                            </div>
                            <h3 class="mt-7 text-xl font-semibold text-brand-700">{{ $title }}</h3>
                            @if ($price)
                                <div class="mt-4 text-4xl font-semibold text-gray-900">{{ $price }}</div>
                                <div class="mt-1 text-sm font-medium text-gray-500">{{ $priceNote }}</div>
                            @endif
                            <ul class="mt-4 min-h-16 space-y-2 text-left text-[19px] leading-8 text-gray-500">
                                @foreach ($items as $item)
                                    <li class="flex gap-2">
                                        <span class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-brand-500"></span>
                                        <span>{{ $item }}</span>
                                    </li>
                                @endforeach
                            </ul>
                            <a
                                class="mt-8 inline-flex rounded-full bg-brand-500 px-6 py-3 text-sm font-semibold text-white hover:bg-brand-600"
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

        <section class="bg-gray-50 px-5 py-16 md:py-20">
            <div class="mx-auto max-w-7xl">
                <div class="grid items-center gap-8 rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-xs md:p-8 lg:grid-cols-[1.2fr_.8fr]">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.3em] text-brand-600">Integração WhatsApp</p>
                        <h2 class="mt-4 text-2xl font-semibold leading-tight text-gray-900 md:text-3xl">
                            Atendimento automático, agendamento por mensagem e IA para interagir com seus clientes.
                        </h2>
                        <p class="mt-4 max-w-3xl text-base leading-7 text-gray-600">
                            Contrate a integração com WhatsApp para receber dúvidas, automatizar respostas, facilitar agendamentos e manter o relacionamento com seus clientes dentro do fluxo do AQAtende.
                        </p>
                        <div class="mt-6 grid gap-3 text-sm text-gray-600 sm:grid-cols-2">
                            <div class="flex gap-2">
                                <span class="mt-1 h-2 w-2 shrink-0 rounded-full bg-brand-500"></span>
                                <span>Respostas automáticas para perguntas frequentes</span>
                            </div>
                            <div class="flex gap-2">
                                <span class="mt-1 h-2 w-2 shrink-0 rounded-full bg-brand-500"></span>
                                <span>Agendamento por mensagem</span>
                            </div>
                            <div class="flex gap-2">
                                <span class="mt-1 h-2 w-2 shrink-0 rounded-full bg-brand-500"></span>
                                <span>IA de interação com o cliente</span>
                            </div>
                            <div class="flex gap-2">
                                <span class="mt-1 h-2 w-2 shrink-0 rounded-full bg-brand-500"></span>
                                <span>Mais agilidade no atendimento da recepção</span>
                            </div>
                        </div>
                    </div>
                    <div class="rounded-2xl border border-brand-100 bg-brand-50 p-6 text-center">
                        <div class="text-sm font-semibold uppercase tracking-[0.2em] text-brand-700">Adicional ao plano</div>
                        <div class="mt-4 text-4xl font-semibold text-brand-900">R$ 19,90</div>
                        <div class="mt-1 text-sm font-medium text-brand-700">por mês</div>
                        <a
                            class="mt-6 inline-flex rounded-full bg-brand-500 px-6 py-3 text-sm font-semibold text-white hover:bg-brand-600"
                            href="https://wa.me/5531993723008"
                            target="_blank"
                            rel="noopener"
                            data-ga-whatsapp-click
                            data-contact-context="whatsapp_addon"
                        >
                            Entre em contato
                        </a>
                        <div class="mt-3 text-sm font-medium text-brand-800">(31) 99372-3008</div>
                    </div>
                </div>
            </div>
        </section>

        <section id="ajuda" class="px-5 py-20 md:py-28">
            <div class="mx-auto max-w-7xl">
                <div class="grid gap-12 lg:grid-cols-[.85fr_1.15fr]">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.3em] text-brand-600">Ajuda e primeiros passos</p>
                        <h2 class="mt-5 text-3xl font-semibold leading-tight md:text-4xl">Um guia rapido para operar sem depender do suporte.</h2>
                        <p class="mt-5 text-lg leading-8 text-gray-600">
                            Consulte os caminhos principais do sistema e as duvidas mais comuns sobre agenda, fila, clientes, profissionais, financeiro e WhatsApp.
                        </p>
                        <div class="mt-8 space-y-3 text-sm text-gray-600">
                            @foreach ([
                                ['1', 'Cadastre servicos e profissionais', 'Defina duracao, valor, comissao e quais profissionais executam cada servico.'],
                                ['2', 'Organize a rotina de atendimento', 'Use agenda para horarios marcados e fila para encaixes ou chegada espontanea.'],
                                ['3', 'Feche o atendimento', 'Ao finalizar, registre o pagamento para alimentar contas a receber, caixa e comissoes.'],
                                ['4', 'Acompanhe o financeiro', 'Use os relatórios e o fluxo de caixa para conferir entradas, saidas e repasses.'],
                            ] as [$number, $title, $text])
                                <div class="flex gap-4 rounded-xl border border-gray-200 bg-white p-4 shadow-theme-xs">
                                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-brand-50 text-sm font-bold text-brand-700">{{ $number }}</div>
                                    <div>
                                        <div class="font-semibold text-gray-900">{{ $title }}</div>
                                        <p class="mt-1 leading-6">{{ $text }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div>
                        <div class="rounded-2xl border border-gray-200 bg-gray-50 p-5 md:p-6">
                            <h3 class="text-xl font-semibold text-gray-900">Principais duvidas</h3>
                            <div class="mt-5 divide-y divide-gray-200 rounded-xl border border-gray-200 bg-white">
                                @foreach ([
                                    ['Como crio um agendamento?', 'Acesse Agenda ou Agendamentos, escolha cliente, servico, profissional, unidade, data e horario. O sistema respeita a agenda do profissional e registra o atendimento.'],
                                    ['Quando uso fila e quando uso agenda?', 'Use agenda para horarios marcados. Use fila para atendimento por ordem de chegada, encaixes ou quando o cliente ja esta na unidade aguardando.'],
                                    ['Como configuro os profissionais?', 'Cadastre o profissional, vincule unidades e servicos, defina se ele esta ativo e configure comissao por percentual ou valor fixo quando necessario.'],
                                    ['Como o financeiro e gerado?', 'Ao criar ou finalizar atendimentos, o AQAtende cria contas a receber e movimentacoes relacionadas. No fechamento do atendimento, informe a forma de pagamento.'],
                                    ['Como funcionam as comissoes?', 'A comissao pode ser configurada no profissional ou por servico. Ao finalizar o atendimento, o sistema calcula o valor do profissional e o valor do salao.'],
                                    ['Como envio link de agendamento para um cliente?', 'No cadastro do cliente, use Gerar link de agendamento. Com WhatsApp ativo, o cliente tambem pode pedir horarios disponiveis pelo bot.'],
                                    ['O cliente pode agendar pelo WhatsApp?', 'Sim. Com o modulo habilitado, ele pode enviar agendar para seguir o fluxo automatico ou disponivel para receber um link com horarios abertos.'],
                                    ['Como evito choque de horario?', 'Configure os horarios dos profissionais e use a agenda do sistema. O bot e o link publico validam disponibilidade antes de criar o agendamento.'],
                                    ['O que fazer se um cliente desistir?', 'Abra o agendamento e cancele, informando o motivo quando aplicavel. O horario volta a ficar livre conforme as regras da agenda.'],
                                    ['Como vejo o resultado do dia?', 'Use o dashboard, fluxo de caixa e relatorios financeiros para acompanhar atendimentos, entradas, contas e comissoes.'],
                                ] as [$question, $answer])
                                    <details class="group p-4 open:bg-brand-50/40">
                                        <summary class="flex cursor-pointer list-none items-center justify-between gap-4 text-sm font-semibold text-gray-900">
                                            <span>{{ $question }}</span>
                                            <span class="text-lg text-brand-600 group-open:rotate-45">+</span>
                                        </summary>
                                        <p class="mt-3 text-sm leading-6 text-gray-600">{{ $answer }}</p>
                                    </details>
                                @endforeach
                            </div>
                        </div>

                        <div class="mt-6 rounded-2xl border border-brand-100 bg-brand-50 p-5 md:p-6">
                            <div class="flex flex-wrap items-start justify-between gap-4">
                                <div>
                                    <p class="text-xs font-bold uppercase tracking-[0.24em] text-brand-700">WhatsApp</p>
                                    <h3 class="mt-2 text-xl font-semibold text-gray-900">Configuracao do atendimento automatico</h3>
                                </div>
                                <span class="rounded-full bg-white px-3 py-1 text-xs font-semibold text-brand-700">Modulo adicional</span>
                            </div>

                            <div class="mt-5 grid gap-3 sm:grid-cols-2">
                                @foreach ([
                                    ['1', 'Conecte a sessao', 'Acesse Configuracoes > WhatsApp > Conexao e gere QR Code ou codigo de pareamento.'],
                                    ['2', 'Ative o bot', 'Na aba Fluxo de agendamento, marque o bot como ativo e salve as configuracoes.'],
                                    ['3', 'Defina o limite da agenda', 'Informe por quantos meses o cliente pode agendar automaticamente, como 3 meses.'],
                                    ['4', 'Libere os servicos', 'Em Servicos, marque quais procedimentos podem aparecer no agendamento via WhatsApp.'],
                                    ['5', 'Teste os comandos', 'Envie agendar para o fluxo automatico ou disponivel para receber o link de horarios.'],
                                    ['6', 'Monitore a conexao', 'Se desconectar, volte em Conexao para atualizar status, gerar novo codigo ou reiniciar a sessao.'],
                                ] as [$number, $title, $text])
                                    <div class="rounded-xl border border-brand-100 bg-white p-4">
                                        <div class="flex items-center gap-3">
                                            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-brand-500 text-xs font-bold text-white">{{ $number }}</div>
                                            <div class="text-sm font-semibold text-gray-900">{{ $title }}</div>
                                        </div>
                                        <p class="mt-3 text-sm leading-6 text-gray-600">{{ $text }}</p>
                                    </div>
                                @endforeach
                            </div>

                            <div class="mt-5 divide-y divide-brand-100 rounded-xl border border-brand-100 bg-white">
                                @foreach ([
                                    ['Qual a diferenca entre agendar e disponivel?', 'Agendar conduz o cliente pelo atendimento automatico, perguntando servico, profissional e horario. Disponivel envia um link para ele escolher entre os horarios abertos.'],
                                    ['O que fazer se o WhatsApp parar de responder?', 'Confira a aba Conexao, atualize o status e, se necessario, reinicie a sessao para gerar um novo QR Code ou codigo de pareamento.'],
                                    ['Por que um servico nao aparece no WhatsApp?', 'Verifique se o servico esta ativo, se esta liberado para agendamento via WhatsApp e se existe profissional ativo vinculado a ele.'],
                                    ['Como evitar agendamento muito distante?', 'Use o campo de abertura da agenda automatica em meses na aba Fluxo de agendamento. O bot bloqueia datas fora desse limite.'],
                                ] as [$question, $answer])
                                    <details class="group p-4 open:bg-brand-50/50">
                                        <summary class="flex cursor-pointer list-none items-center justify-between gap-4 text-sm font-semibold text-gray-900">
                                            <span>{{ $question }}</span>
                                            <span class="text-lg text-brand-600 group-open:rotate-45">+</span>
                                        </summary>
                                        <p class="mt-3 text-sm leading-6 text-gray-600">{{ $answer }}</p>
                                    </details>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section id="contato" class="relative bg-cover bg-center px-5 py-24" style="background-image: linear-gradient(rgba(38,4,58,.76), rgba(38,4,58,.76)), url('{{ asset('landing/img/bg/salon-bg.jpg') }}');">
            <div class="mx-auto max-w-3xl text-center text-white">
                <p class="text-xs font-bold uppercase tracking-[0.3em] text-white/65">Comece agora</p>
                <h2 class="mt-5 text-3xl font-semibold md:text-4xl">Acesse sua empresa e teste o fluxo completo.</h2>
                <p class="mt-5 text-lg leading-8 text-white/75">Use o login de empresa para operar agenda, fila, profissionais, servicos e financeiro.</p>
                <div class="mt-9 flex justify-center gap-3">
                    <a class="rounded-full bg-white px-7 py-3 text-sm font-bold text-brand-800 hover:bg-brand-50" href="{{ route('login', ['mode' => 'company']) }}">Entrar como empresa</a>
                </div>
            </div>
        </section>
    </main>

    <footer class="bg-[#180225] px-5 py-10 text-white">
        <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-5">
            <div class="flex items-center gap-3">
                <img class="h-12 w-12 object-contain" src="{{ asset('logo.png') }}" alt="AQAtende">
                <div>
                    <div class="text-sm font-semibold uppercase tracking-[0.24em]">AQAtende</div>
                    <div class="text-xs text-white/55">Sistema de atendimento para saloes</div>
                </div>
            </div>
            <div class="text-sm text-white/55">Agenda · Fila · Profissionais · Financeiro</div>
        </div>
    </footer>
    @if (config('services.google_analytics.measurement_id'))
        <script>
            document.querySelectorAll('[data-ga-plan-click]').forEach((link) => {
                link.addEventListener('click', () => {
                    if (typeof gtag !== 'function') {
                        return;
                    }

                    gtag('event', 'select_plan', {
                        plan_slug: link.dataset.planSlug,
                        plan_name: link.dataset.planName,
                        link_url: link.href,
                    });
                });
            });

            document.querySelectorAll('[data-ga-whatsapp-click]').forEach((link) => {
                link.addEventListener('click', () => {
                    if (typeof gtag !== 'function') {
                        return;
                    }

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
