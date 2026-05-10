<x-app-layout>
    <x-slot name="header">
        <div>
            <div class="text-xs uppercase text-gray-400">Configuracoes</div>
            <h2 class="text-lg font-semibold text-gray-800">WhatsApp</h2>
        </div>
    </x-slot>

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
                        Configure e acompanhe a conexao WhatsApp usada para automacoes, mensagens e links de agendamento.
                    </p>
                </div>
                <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $apiConfigured ? 'bg-emerald-100 text-emerald-800' : 'bg-warning-100 text-warning-800' }}">
                    {{ $apiConfigured ? 'API configurada' : 'API nao configurada' }}
                </span>
            </div>
        </div>

        <div class="grid gap-4 lg:grid-cols-12">
            <section class="rounded-xl border border-gray-200 bg-white p-6 shadow-theme-sm lg:col-span-7">
                <div class="text-xs uppercase text-gray-400">Conexao</div>
                <h3 class="mt-1 text-base font-semibold text-gray-800">Sessao WhatsApp da empresa</h3>
                <p class="mt-2 text-sm text-gray-500">
                    Esta area vai concentrar a geracao do QR Code, status da conexao e envio de teste usando o servico AQATech Comunicacao.
                </p>

                @php
                    $status = $session['status'] ?? null;
                    $statusLabel = match ($status) {
                        'connected' => 'Conectado',
                        'qr_pending' => 'Aguardando leitura do QR Code',
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
                    @elseif (! $session)
                        <div class="mt-4 text-sm text-gray-500">Nenhuma sessao vinculada nesta tela ainda.</div>
                    @endif
                </div>

                <div class="mt-5 flex flex-wrap gap-2">
                    <form method="POST" action="{{ route('settings.whatsapp.qr') }}">
                        @csrf
                        <button type="submit" class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-semibold text-white shadow-theme-xs hover:bg-brand-600 disabled:cursor-not-allowed disabled:opacity-60" @disabled(! $apiConfigured)>
                            Gerar QR Code
                        </button>
                    </form>
                    <form method="POST" action="{{ route('settings.whatsapp.status') }}">
                        @csrf
                        <button type="submit" class="rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-theme-xs hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-60" @disabled(! $apiConfigured || ! $session)>
                            Atualizar status
                        </button>
                    </form>
                </div>
            </section>

            <aside class="rounded-xl border border-gray-200 bg-white p-6 shadow-theme-sm lg:col-span-5">
                <div class="text-xs uppercase text-gray-400">Servico</div>
                <h3 class="mt-1 text-base font-semibold text-gray-800">AQATech Comunicacao</h3>

                <div class="mt-4 space-y-3 text-sm">
                    <div class="rounded-lg border border-gray-100 bg-gray-50 p-3">
                        <div class="text-xs text-gray-500">Endpoint</div>
                        <div class="mt-1 break-all font-medium text-gray-800">{{ $apiUrl ?: 'COMMUNICATION_API_URL ausente' }}</div>
                    </div>
                    <div class="rounded-lg border border-gray-100 bg-gray-50 p-3">
                        <div class="text-xs text-gray-500">Token</div>
                        <div class="mt-1 font-medium text-gray-800">{{ $apiConfigured ? 'Configurado' : 'COMMUNICATION_API_TOKEN ausente' }}</div>
                    </div>
                </div>

                @unless ($apiConfigured)
                    <div class="mt-4 rounded-lg border border-warning-200 bg-warning-50 p-3 text-sm text-warning-800">
                        Preencha as variaveis COMMUNICATION_API_URL e COMMUNICATION_API_TOKEN no ambiente para habilitar a integracao.
                    </div>
                @endunless
            </aside>
        </div>
    </div>
</x-app-layout>
