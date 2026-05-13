@php
    $companyId = session('active_company_id');
    $company = $companyId ? \App\Models\Company::find($companyId) : null;
    $licenseEnforcer = app(\App\Services\Licenses\LicenseEnforcer::class);
    $hasWhatsappModule = $companyId ? $licenseEnforcer->hasModule((int) $companyId, 'whatsapp') : false;
    $companyLogo = $companyId
        ? \App\Models\CompanySetting::where('company_id', $companyId)->where('key', 'logo_path')->value('value')
        : null;
    $menuItems = [
        'home' => [
            ['label' => 'Dashboard', 'route' => 'dashboard', 'icon' => 'M4 13.5 12 5l8 8.5M6.5 12v7h11v-7M10 19v-5h4v5'],
        ],
        'cadastro' => [
            ['label' => 'Empresa', 'route' => 'clinics.index', 'permission' => 'cadastro.clinicas.view', 'icon' => 'M4 20h16M6 20V7l6-3 6 3v13M9 11h1M14 11h1M9 15h1M14 15h1'],
            ['label' => 'Unidades', 'route' => 'units.index', 'permission' => 'cadastro.unidades.view', 'icon' => 'M12 21s6-5.1 6-10a6 6 0 1 0-12 0c0 4.9 6 10 6 10zM12 13a2 2 0 1 0 0-4 2 2 0 0 0 0 4z'],
            ['label' => 'Profissionais', 'route' => 'professionals.index', 'permission' => 'cadastro.profissionais.view', 'icon' => 'M14.5 4.5 5 14m4-4 5 5M4.5 19.5l4-4M15.5 8.5l4-4M6 17a2 2 0 1 0-2 2m14-14a2 2 0 1 0 2-2'],
            ['label' => 'Servicos', 'route' => 'services.index', 'permission' => 'cadastro.servicos.view', 'icon' => 'M7 3v18M17 3v18M7 8h10M7 13h10M7 18h10M4 6h3M4 11h3M4 16h3M17 6h3M17 11h3M17 16h3'],
            ['label' => 'Clientes', 'route' => 'patients.index', 'permission' => 'cadastro.pacientes.view', 'icon' => 'M8 10a4 4 0 1 0 8 0 4 4 0 0 0-8 0zM4.5 21c.8-4 3.6-6 7.5-6s6.7 2 7.5 6'],
        ],
        'agendamento' => [
            ['label' => 'Agenda', 'route' => 'agenda.index', 'permission' => 'agendamento.agenda.view', 'icon' => 'M7 3v3M17 3v3M4 8h16M6 5h12a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2zM8 12h3M13 12h3M8 16h3'],
            ['label' => 'Agendamentos', 'route' => 'appointments.index', 'permission' => 'agendamento.agendamentos.view', 'icon' => 'M9 11l2 2 4-5M7 3v3M17 3v3M4 8h16M6 5h12a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2z'],
            ['label' => 'Fila', 'route' => 'queue.index', 'permission' => 'atendimento.atendimentos.view', 'icon' => 'M4 7h10M4 12h16M4 17h12M18 6l3 3-3 3'],
        ],
        'financeiro' => [
            ['label' => 'Contas a Pagar', 'route' => 'finance.payables.index', 'permission' => 'financeiro.contas_pagar.view', 'icon' => 'M12 3v18M17 7.5c-.8-1-2.2-1.5-4-1.5-2.3 0-4 1.1-4 2.8 0 4 8 1.9 8 6.2 0 1.7-1.7 3-4 3-2 0-3.5-.6-4.5-1.7'],
            ['label' => 'Contas a Receber', 'route' => 'finance.receivables.index', 'permission' => 'financeiro.contas_receber.view', 'icon' => 'M12 3v18M8 9l4-4 4 4M8 15h8M6 21h12'],
            ['label' => 'Fluxo de Caixa', 'route' => 'finance.cashflow.index', 'permission' => 'financeiro.fluxo_caixa.view', 'icon' => 'M4 17h4l3-10 3 10h6M5 7h3M16 7h3M17.5 5.5 19 7l-1.5 1.5M6.5 5.5 5 7l1.5 1.5'],
            ['label' => 'Contas Bancarias', 'route' => 'finance.accounts.index', 'permission' => 'financeiro.contas_bancarias.view', 'icon' => 'M4 10h16M6 10v8M10 10v8M14 10v8M18 10v8M3 18h18M4 7l8-4 8 4z'],
            ['label' => 'Categorias', 'route' => 'finance.categories.index', 'permission' => 'financeiro.categorias.view', 'icon' => 'M4 7h7v7H4zM13 7h7v7h-7zM4 16h7v4H4zM13 16h7v4h-7z'],
            ['label' => 'Relatorios', 'route' => 'finance.reports', 'permission' => 'financeiro.relatorios.view', 'icon' => 'M5 19V5M5 19h14M9 16v-5M13 16V8M17 16v-8'],
        ],
        'seguranca' => [
            ['label' => 'Usuarios', 'route' => 'security.users.index', 'permission' => 'seguranca.usuarios.view', 'icon' => 'M8 10a4 4 0 1 0 8 0 4 4 0 0 0-8 0zM4.5 21c.8-4 3.6-6 7.5-6s6.7 2 7.5 6'],
            ['label' => 'Perfis', 'route' => 'security.roles.index', 'permission' => 'seguranca.perfis.view', 'icon' => 'M12 3l7 4v5c0 4.5-2.8 7.5-7 9-4.2-1.5-7-4.5-7-9V7l7-4zM9.5 12l1.7 1.7 3.5-4'],
        ],
        'configuracoes' => [
            ['label' => 'Licenca', 'route' => 'settings.license', 'permission' => 'configuracoes.logo.view', 'icon' => 'M7 4h10v16H7zM9 8h6M9 12h6M9 16h3'],
            ['label' => 'Logo da empresa', 'route' => 'settings.logo', 'permission' => 'configuracoes.logo.view', 'icon' => 'M4 5h16v14H4zM8 13l2.5-3 2 2.5L15 9l5 7M8 8h.01'],
            ['label' => 'WhatsApp', 'route' => 'settings.whatsapp', 'permission' => 'configuracoes.logo.view', 'requires_module' => 'whatsapp', 'icon' => 'M20 11.5a8 8 0 0 1-11.8 7L4 20l1.5-4.1A8 8 0 1 1 20 11.5zM9 8.5c.2 2.9 2.1 5 5 5.5l1.2-1.2c.2-.2.2-.5 0-.7l-1.1-1.1c-.2-.2-.5-.2-.7 0l-.6.6c-1-.4-1.8-1.2-2.2-2.2l.6-.6c.2-.2.2-.5 0-.7L10.1 7c-.2-.2-.5-.2-.7 0L9 7.4c-.1.2-.1.6 0 1.1z'],
            ['label' => 'Termo de uso', 'route' => 'settings.terms.edit', 'admin_only' => true, 'icon' => 'M6 4h9l3 3v13H6zM9 10h6M9 14h6M9 18h4'],
        ],
    ];

    if (auth()->user()->is_platform_admin) {
        $menuItems['administrativo'] = [
            ['label' => 'Empresas', 'route' => 'admin.companies.index', 'admin_only' => true, 'icon' => 'M4 20h16M6 20V7l6-3 6 3v13M9 11h1M14 11h1M9 15h1M14 15h1'],
            ['label' => 'Usuarios master', 'route' => 'admin.masters.index', 'admin_only' => true, 'icon' => 'M12 3l7 4v5c0 4.5-2.8 7.5-7 9-4.2-1.5-7-4.5-7-9V7l7-4zM9.5 12l1.7 1.7 3.5-4'],
        ];
    }
@endphp

<aside id="sidebar" class="fixed top-0 left-0 z-9999 h-screen overflow-y-auto border-r border-gray-200 bg-white px-4 pt-4 transition-all duration-300 ease-in-out">
    <a class="pb-3 flex items-center gap-2 sidebar-title" href="{{ route('dashboard') }}">
        <img class="h-16 w-16 rounded-full object-contain border border-brand-100 bg-white" src="{{ $companyLogo ? asset('storage/'.$companyLogo) : asset('logo.png') }}" alt="AQAtende" />
        <span class="text-lg font-semibold text-gray-800">{{ $company?->name ?? 'AQAtende' }}</span>
    </a>
    <div class="flex flex-col pb-6">
        <nav class="mb-6">
            <div class="flex flex-col gap-4">
                @foreach ($menuItems as $section => $items)
                    @php
                    $visibleItems = collect($items)->filter(function ($item) use ($companyId, $hasWhatsappModule) {
                        if (auth()->user()->is_platform_admin) {
                            return true;
                        }

                        if (($item['requires_module'] ?? null) === 'whatsapp' && ! $hasWhatsappModule) {
                            return false;
                        }

                        if (! empty($item['admin_only'])) {
                            return auth()->user()->is_platform_admin;
                        }

                        if (empty($item['permission'])) {
                            return true;
                        }

                        return $companyId && auth()->user()->hasCompanyPermission($companyId, $item['permission']);
                    })->values();
                    @endphp
                    @if ($visibleItems->isEmpty())
                        @continue
                    @endif
                    <div>
                        <h2 class="menu-section mb-3 text-xs uppercase flex leading-[20px] text-gray-400">
                            {{ ucfirst($section) }}
                        </h2>
                        <ul class="flex flex-col gap-2">
                            @foreach ($visibleItems as $item)
                                @php
                                    $active = request()->routeIs($item['route']);
                                    $linkClass = $active ? 'menu-item menu-item-active group' : 'menu-item menu-item-inactive group';
                                    $iconClass = $active ? 'menu-item-icon-active' : 'menu-item-icon-inactive';
                                @endphp
                                <li>
                                    <a href="{{ route($item['route']) }}" class="{{ $linkClass }}" title="{{ $item['label'] }}">
                                        <span class="{{ $iconClass }}">
                                            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="{{ $item['icon'] }}" />
                                            </svg>
                                        </span>
                                        <span class="menu-text">{{ $item['label'] }}</span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </div>
        </nav>
        <div class="menu-text mt-auto border-t border-gray-100 pt-3 text-xs text-gray-400">
            Versao {{ config('app.version') }}
        </div>
    </div>
</aside>
<div id="sidebar-backdrop" class="fixed inset-0 bg-black/40 z-50 hidden lg:hidden"></div>
