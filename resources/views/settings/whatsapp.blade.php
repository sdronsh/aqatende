<x-app-layout>
    <x-slot name="header">
        <div>
            <div class="text-xs uppercase text-gray-400">Configuracoes</div>
            <h2 class="text-lg font-semibold text-gray-800">WhatsApp</h2>
        </div>
    </x-slot>

    @php
        $activeCompanyId = session('active_company_id');
        $showWhatsappTechnicalInfo = auth()->user()?->is_platform_admin
            || ($activeCompanyId && auth()->user()?->isCompanyMaster((int) $activeCompanyId));
        $tabs = [
            'templates' => 'Templates',
            'campanhas' => 'Campanhas',
            'fluxo' => 'Fluxo de agendamento',
            'regras' => 'Regras',
            'conexao' => 'Conexao',
        ];
    @endphp

    <div class="space-y-4">
        @if (session('status'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800 shadow-theme-sm">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-xl border border-error-200 bg-error-50 p-4 text-sm text-error-700 shadow-theme-sm">
                {{ $errors->first() }}
            </div>
        @endif

        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-theme-sm">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <div class="text-sm font-semibold text-gray-800">{{ $company->name }}</div>
                    <p class="mt-1 text-sm text-gray-500">
                        Templates, campanhas e fluxo de agendamento via WhatsApp.
                    </p>
                </div>
                <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $apiConfigured ? 'bg-emerald-100 text-emerald-800' : 'bg-warning-100 text-warning-800' }}">
                    {{ $apiConfigured ? 'API configurada' : 'API nao configurada' }}
                </span>
            </div>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-2 shadow-theme-sm">
            <nav class="flex flex-wrap gap-2">
                @foreach ($tabs as $key => $label)
                    <a
                        href="{{ route('settings.whatsapp', ['tab' => $key]) }}"
                        class="rounded-lg px-3 py-2 text-sm font-semibold {{ $activeTab === $key ? 'bg-brand-500 text-white' : 'bg-gray-50 text-gray-700 hover:bg-gray-100' }}"
                    >
                        {{ $label }}
                    </a>
                @endforeach
            </nav>
        </div>

        @if ($activeTab === 'templates')
            <section class="rounded-xl border border-gray-200 bg-white p-6 shadow-theme-sm">
                <h3 class="text-base font-semibold text-gray-800">Templates de mensagens</h3>
                <p class="mt-2 text-sm text-gray-500">Use variaveis como <code>{nome}</code>, <code>{servico}</code> e <code>{data_hora}</code>.</p>

                <form method="POST" action="{{ route('settings.whatsapp.automation') }}" class="mt-5 space-y-4">
                    @csrf
                    <input type="hidden" name="tab" value="templates">

                    <div>
                        <label for="template_welcome" class="mb-1 block text-xs font-semibold uppercase text-gray-500">Boas-vindas</label>
                        <textarea id="template_welcome" name="template_welcome" rows="3" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm text-gray-700">{{ old('template_welcome', data_get($automation, 'templates.welcome')) }}</textarea>
                    </div>
                    <div>
                        <label for="template_inactive" class="mb-1 block text-xs font-semibold uppercase text-gray-500">Cliente sumido</label>
                        <textarea id="template_inactive" name="template_inactive" rows="3" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm text-gray-700">{{ old('template_inactive', data_get($automation, 'templates.inactive')) }}</textarea>
                    </div>
                    <div>
                        <label for="template_birthday" class="mb-1 block text-xs font-semibold uppercase text-gray-500">Aniversario</label>
                        <textarea id="template_birthday" name="template_birthday" rows="3" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm text-gray-700">{{ old('template_birthday', data_get($automation, 'templates.birthday')) }}</textarea>
                    </div>

                    <button type="submit" class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-600">Salvar templates</button>
                </form>
            </section>
        @endif

        @if ($activeTab === 'campanhas')
            @php
                $campaignTypeLabels = [
                    'all' => 'Todos os clientes',
                    'inactive' => 'Clientes sumidos',
                    'birthday' => 'Aniversariantes de hoje',
                ];
                $campaignStatusLabels = [
                    'draft' => 'Preparada',
                    'sending' => 'Enviando',
                    'completed' => 'Concluida',
                    'failed' => 'Com falhas',
                ];
                $campaignStatusClasses = [
                    'draft' => 'bg-warning-100 text-warning-800',
                    'sending' => 'bg-brand-50 text-brand-700',
                    'completed' => 'bg-emerald-100 text-emerald-800',
                    'failed' => 'bg-error-100 text-error-700',
                ];
            @endphp

            <section class="rounded-xl border border-gray-200 bg-white p-6 shadow-theme-sm">
                <h3 class="text-base font-semibold text-gray-800">Disparo manual de campanhas</h3>
                <p class="mt-2 text-sm text-gray-500">Prepare o publico, confira a quantidade de destinatarios e dispare pelo WhatsApp conectado da empresa.</p>

                <form method="POST" action="{{ route('settings.whatsapp.campaigns.store') }}" class="mt-5 space-y-4">
                    @csrf

                    <div class="grid gap-4 lg:grid-cols-3">
                        <div>
                            <label for="campaign_name" class="mb-1 block text-xs font-semibold uppercase text-gray-500">Nome da campanha</label>
                            <input id="campaign_name" name="name" value="{{ old('name') }}" placeholder="Ex: Clientes sumidos - Maio" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm text-gray-700">
                        </div>
                        <div>
                            <label for="campaign_type" class="mb-1 block text-xs font-semibold uppercase text-gray-500">Publico</label>
                            <select id="campaign_type" name="type" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm text-gray-700">
                                <option value="all" @selected(old('type') === 'all')>Todos os clientes</option>
                                <option value="inactive" @selected(old('type', 'inactive') === 'inactive')>Clientes sumidos</option>
                                <option value="birthday" @selected(old('type') === 'birthday')>Aniversariantes de hoje</option>
                            </select>
                        </div>
                        <div>
                            <label for="campaign_inactive_days" class="mb-1 block text-xs font-semibold uppercase text-gray-500">Dias sem comparecer</label>
                            <input id="campaign_inactive_days" name="inactive_days" type="number" min="1" max="365" value="{{ old('inactive_days', data_get($automation, 'campaigns.inactive_days', 30)) }}" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm text-gray-700">
                        </div>
                    </div>

                    <div>
                        <label for="campaign_message" class="mb-1 block text-xs font-semibold uppercase text-gray-500">Mensagem</label>
                        <textarea id="campaign_message" name="message" rows="4" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm text-gray-700" placeholder="Oi, {primeiro_nome}! Sentimos sua falta. Quer agendar um novo horario?">{{ old('message', data_get($automation, 'templates.inactive')) }}</textarea>
                        <p class="mt-1 text-xs text-gray-500">Variaveis disponiveis: <code>{nome}</code>, <code>{primeiro_nome}</code>, <code>{cliente}</code> e <code>{dias}</code>.</p>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <button type="submit" class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-600">
                            Preparar campanha
                        </button>
                        <span class="text-xs text-gray-500">O envio so acontece depois que voce clicar em Disparar agora no historico abaixo.</span>
                    </div>
                </form>
            </section>

            <section class="rounded-xl border border-gray-200 bg-white p-6 shadow-theme-sm">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h3 class="text-base font-semibold text-gray-800">Ultimas campanhas</h3>
                        <p class="mt-1 text-sm text-gray-500">Campanhas preparadas e disparadas manualmente.</p>
                    </div>
                    <span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-600">{{ $campaigns->count() }} registro(s)</span>
                </div>

                <div class="mt-5 overflow-x-auto">
                    <table class="min-w-full border-collapse text-left text-sm">
                        <thead>
                            <tr class="border-b border-gray-100 text-xs uppercase text-gray-400">
                                <th class="py-3 pr-4">Campanha</th>
                                <th class="py-3 pr-4">Publico</th>
                                <th class="py-3 pr-4">Status</th>
                                <th class="py-3 pr-4">Envio</th>
                                <th class="py-3 text-right">Acao</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($campaigns as $campaign)
                                <tr>
                                    <td class="py-3 pr-4">
                                        <div class="font-semibold text-gray-800">{{ $campaign->name }}</div>
                                        <div class="mt-1 text-xs text-gray-500">{{ $campaign->created_at?->format('d/m/Y H:i') }}</div>
                                    </td>
                                    <td class="py-3 pr-4 text-gray-600">
                                        {{ $campaignTypeLabels[$campaign->type] ?? $campaign->type }}
                                        @if ($campaign->inactive_days)
                                            <div class="text-xs text-gray-400">{{ $campaign->inactive_days }} dia(s) sem comparecer</div>
                                        @endif
                                    </td>
                                    <td class="py-3 pr-4">
                                        <span class="inline-flex rounded-full px-2 py-1 text-xs font-semibold {{ $campaignStatusClasses[$campaign->status] ?? 'bg-gray-100 text-gray-700' }}">
                                            {{ $campaignStatusLabels[$campaign->status] ?? $campaign->status }}
                                        </span>
                                    </td>
                                    <td class="py-3 pr-4 text-gray-600">
                                        {{ $campaign->sent_count }}/{{ $campaign->recipients_count }} enviada(s)
                                        @if ($campaign->failed_count > 0)
                                            <div class="text-xs text-error-600">{{ $campaign->failed_count }} falha(s)</div>
                                        @endif
                                    </td>
                                    <td class="py-3 text-right">
                                        @if ($campaign->status === 'draft' || $campaign->status === 'failed')
                                            <form method="POST" action="{{ route('settings.whatsapp.campaigns.send', $campaign) }}" onsubmit="return confirm('Disparar esta campanha para {{ $campaign->recipients_count }} destinatario(s)?')">
                                                @csrf
                                                <button type="submit" class="rounded-lg bg-brand-500 px-3 py-2 text-xs font-semibold text-white hover:bg-brand-600" @disabled(! $apiConfigured || $campaign->recipients_count < 1)>
                                                    Disparar agora
                                                </button>
                                            </form>
                                        @else
                                            <span class="text-xs text-gray-400">Sem acao</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-6 text-center text-sm text-gray-500">Nenhuma campanha preparada ainda.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

        @endif

        @if ($activeTab === 'fluxo')
            <section class="rounded-xl border border-gray-200 bg-white p-6 shadow-theme-sm">
                <h3 class="text-base font-semibold text-gray-800">Fluxo de agendamento</h3>
                <p class="mt-2 text-sm text-gray-500">Fluxo alvo: agendar, selecionar servico, profissional (ou qualquer um), horario e confirmar.</p>

                <form method="POST" action="{{ route('settings.whatsapp.automation') }}" class="mt-5 space-y-4">
                    @csrf
                    <input type="hidden" name="tab" value="fluxo">

                    <label class="flex items-center gap-2 text-sm text-gray-700">
                        <input type="checkbox" name="bot_enabled" value="1" class="h-4 w-4 rounded border-gray-300 text-brand-500" @checked(old('bot_enabled', data_get($automation, 'flow.bot_enabled')))>
                        Ativar bot de agendamento
                    </label>

                    <label class="flex items-center gap-2 text-sm text-gray-700">
                        <input type="checkbox" name="bot_allow_any_professional" value="1" class="h-4 w-4 rounded border-gray-300 text-brand-500" @checked(old('bot_allow_any_professional', data_get($automation, 'flow.bot_allow_any_professional', true)))>
                        Permitir opcao "qualquer profissional"
                    </label>

                    <div>
                        <label for="booking_window_months" class="mb-1 block text-xs font-semibold uppercase text-gray-500">Abertura da agenda automatica em meses</label>
                        <input id="booking_window_months" name="booking_window_months" type="number" min="1" max="12" value="{{ old('booking_window_months', data_get($automation, 'flow.booking_window_months', 3)) }}" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm text-gray-700 sm:w-48">
                        <p class="mt-1 text-xs text-gray-500">O bot so aceita horarios entre agora e o limite informado.</p>
                    </div>

                    <div>
                        <label for="bot_confirmation_template" class="mb-1 block text-xs font-semibold uppercase text-gray-500">Template de confirmacao</label>
                        <textarea id="bot_confirmation_template" name="bot_confirmation_template" rows="3" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm text-gray-700">{{ old('bot_confirmation_template', data_get($automation, 'flow.bot_confirmation_template')) }}</textarea>
                    </div>

                    <button type="submit" class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-600">Salvar fluxo</button>
                </form>
            </section>
        @endif

        @if ($activeTab === 'regras')
            <section class="rounded-xl border border-gray-200 bg-white p-6 shadow-theme-sm">
                <h3 class="text-base font-semibold text-gray-800">Regras atuais do fluxo</h3>
                <p class="mt-2 text-sm text-gray-500">Estas regras ficam registradas para orientar a execucao no backend do bot.</p>

                <div class="mt-5 rounded-lg border border-gray-100 bg-gray-50 p-4 text-sm text-gray-700">
                    <ol class="list-decimal space-y-2 pl-5">
                        <li>Perguntar se deseja agendar.</li>
                        <li>Selecionar servico.</li>
                        <li>Perguntar profissional ou qualquer um.</li>
                        <li>Selecionar horario disponivel.</li>
                        <li>Confirmar e agendar automaticamente.</li>
                    </ol>
                </div>
            </section>
        @endif

        @if ($activeTab === 'conexao')
            @php
                $status = $session['status'] ?? null;
                $statusLabel = match ($status) {
                    'connected' => 'Conectado',
                    'qr_pending' => 'Aguardando leitura do QR Code',
                    'pairing_code' => 'Aguardando codigo de pareamento',
                    'connecting' => 'Conectando',
                    'error' => 'Erro',
                    'disconnected' => 'Desconectado',
                    default => $status ? ucfirst((string) $status) : 'Sem sessao',
                };
                $statusClass = $status === 'connected'
                    ? 'bg-emerald-100 text-emerald-800'
                    : ($status === 'error' ? 'bg-error-100 text-error-700' : 'bg-warning-100 text-warning-800');
                $qrCode = $session['qr_code'] ?? null;
            @endphp

            <div class="grid gap-4 @if ($showWhatsappTechnicalInfo) lg:grid-cols-12 @endif">
                <section class="rounded-xl border border-gray-200 bg-white p-6 shadow-theme-sm @if ($showWhatsappTechnicalInfo) lg:col-span-7 @endif">
                    <div class="text-xs uppercase text-gray-400">Conexao</div>
                    <h3 class="mt-1 text-base font-semibold text-gray-800">Sessao WhatsApp da empresa</h3>

                    <div class="mt-5 rounded-lg border border-gray-100 bg-gray-50 p-4">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <div class="text-xs text-gray-500">Status</div>
                                <div class="mt-1 inline-flex rounded-full px-2 py-1 text-xs font-semibold {{ $statusClass }}">{{ $statusLabel }}</div>
                            </div>
                            @if (! empty($session['phone_number']))
                                <div class="text-right">
                                    <div class="text-xs text-gray-500">Telefone conectado</div>
                                    <div class="mt-1 text-sm font-semibold text-gray-800">{{ $session['phone_number'] }}</div>
                                </div>
                            @endif
                        </div>

                        @if (! empty($session['last_error']))
                            <div class="mt-4 rounded-lg border border-error-200 bg-error-50 p-3 text-sm text-error-700">
                                {{ $session['last_error'] }}
                            </div>
                        @endif

                        @if ($qrCode && $status !== 'connected')
                            <div class="mt-4">
                                <div class="mb-2 text-xs font-semibold uppercase text-gray-400">QR Code</div>
                                @if (str_starts_with($qrCode, 'data:image'))
                                    <img class="h-64 w-64 rounded-lg border border-gray-200 bg-white p-2" src="{{ $qrCode }}" alt="QR Code WhatsApp">
                                @else
                                    <div class="break-all rounded-lg border border-gray-200 bg-white p-3 font-mono text-xs text-gray-700">{{ $qrCode }}</div>
                                @endif
                            </div>
                        @elseif (! empty($session['pairing_code']) && $status !== 'connected')
                            <div class="mt-4 rounded-lg border border-brand-100 bg-brand-50 p-4">
                                <div class="text-xs font-semibold uppercase text-brand-700">Codigo de pareamento</div>
                                <div class="mt-2 inline-flex rounded-lg border border-brand-200 bg-white px-4 py-3 font-mono text-2xl font-bold tracking-widest text-brand-700">
                                    {{ substr($session['pairing_code'], 0, 4).'-'.substr($session['pairing_code'], 4) }}
                                </div>
                            </div>
                        @elseif (! $session)
                            <div class="mt-4 text-sm text-gray-500">Nenhuma sessao vinculada nesta tela ainda.</div>
                        @endif
                    </div>

                    <div class="mt-5 flex flex-wrap gap-2">
                        <form method="POST" action="{{ route('settings.whatsapp.pairing-code') }}" class="flex w-full flex-col gap-2 rounded-lg border border-gray-100 bg-gray-50 p-3 sm:flex-row sm:items-end">
                            @csrf
                            <input type="hidden" name="tab" value="conexao">
                            <div class="flex-1">
                                <label class="mb-1 block text-xs font-semibold uppercase text-gray-500" for="phone">Telefone do WhatsApp</label>
                                <input id="phone" name="phone" value="{{ old('phone', $session['pairing_phone'] ?? $session['phone_number'] ?? '') }}" placeholder="(31) 99999-9999" inputmode="numeric" autocomplete="tel-national" class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700">
                            </div>
                            <button type="submit" class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-600" @disabled(! $apiConfigured)>
                                Gerar codigo
                            </button>
                        </form>
                        <form method="POST" action="{{ route('settings.whatsapp.qr') }}">
                            @csrf
                            <input type="hidden" name="tab" value="conexao">
                            <button type="submit" class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-600" @disabled(! $apiConfigured)>
                                Gerar QR Code
                            </button>
                        </form>
                        <form method="POST" action="{{ route('settings.whatsapp.status') }}">
                            @csrf
                            <input type="hidden" name="tab" value="conexao">
                            <button type="submit" class="rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50" @disabled(! $apiConfigured || ! $session)>
                                Atualizar status
                            </button>
                        </form>
                        <form method="POST" action="{{ route('settings.whatsapp.reset') }}" onsubmit="return confirm('Reiniciar a sessao WhatsApp e gerar uma nova conexao?')">
                            @csrf
                            @method('DELETE')
                            <input type="hidden" name="tab" value="conexao">
                            <button type="submit" class="rounded-lg border border-error-200 bg-white px-4 py-2 text-sm font-semibold text-error-700 hover:bg-error-50" @disabled(! $session)>
                                Reiniciar sessao
                            </button>
                        </form>
                    </div>
                </section>

                @if ($showWhatsappTechnicalInfo)
                    <aside class="rounded-xl border border-gray-200 bg-white p-6 shadow-theme-sm lg:col-span-5">
                        <div class="text-xs uppercase text-gray-400">Servico</div>
                        <h3 class="mt-1 text-base font-semibold text-gray-800">AQATech Comunicacao</h3>
                        <div class="mt-4 space-y-3 text-sm">
                            <div class="rounded-lg border border-gray-100 bg-gray-50 p-3">
                                <div class="text-xs text-gray-500">Endpoint</div>
                                <div class="mt-1 break-all font-medium text-gray-800">{{ $apiUrl ?: 'COMMUNICATION_API_URL ausente' }}</div>
                            </div>
                            <div class="rounded-lg border border-gray-100 bg-gray-50 p-3">
                                <div class="text-xs text-gray-500">Webhook de entrada (configurar no Comunicacao)</div>
                                <div class="mt-1 break-all font-medium text-gray-800">{{ $webhookUrl }}</div>
                            </div>
                            <div class="rounded-lg border border-gray-100 bg-gray-50 p-3">
                                <div class="text-xs text-gray-500">Token do webhook</div>
                                <div class="mt-1 font-medium {{ $webhookTokenConfigured ? 'text-emerald-700' : 'text-warning-700' }}">
                                    {{ $webhookTokenConfigured ? 'Configurado em COMMUNICATION_WEBHOOK_TOKEN' : 'Nao configurado' }}
                                </div>
                            </div>
                        </div>
                    </aside>
                @endif
            </div>
        @endif
    </div>

    <script>
        (() => {
            const input = document.getElementById('phone');
            if (!input) return;
            const formatPhone = value => {
                let digits = String(value || '').replace(/\D+/g, '');
                if (digits.startsWith('55') && digits.length > 11) digits = digits.slice(2);
                digits = digits.slice(0, 11);
                if (digits.length <= 2) return digits;
                if (digits.length <= 6) return `(${digits.slice(0, 2)}) ${digits.slice(2)}`;
                if (digits.length <= 10) return `(${digits.slice(0, 2)}) ${digits.slice(2, 6)}-${digits.slice(6)}`;
                return `(${digits.slice(0, 2)}) ${digits.slice(2, 7)}-${digits.slice(7)}`;
            };
            input.value = formatPhone(input.value);
            input.addEventListener('input', () => { input.value = formatPhone(input.value); });
        })();
    </script>
</x-app-layout>
