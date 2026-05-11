<div
    class="fixed inset-x-3 bottom-3 z-999999 hidden rounded-xl border border-brand-100 bg-white p-4 shadow-theme-lg sm:left-auto sm:right-4 sm:max-w-sm"
    data-pwa-install-prompt
    role="dialog"
    aria-live="polite"
    aria-label="Instalar AQAtende"
>
    <div class="flex items-start gap-3">
        <img class="h-10 w-10 shrink-0 rounded-lg border border-brand-100 bg-white object-contain" src="{{ asset('icons/icon-192.png') }}" alt="AQAtende">
        <div class="min-w-0 flex-1">
            <div class="text-sm font-semibold text-gray-900">Instalar AQAtende</div>
            <div class="mt-1 text-sm text-gray-600" data-pwa-install-text>
                Preparando a instalacao para acesso rapido pela tela inicial do celular.
            </div>
            <div class="mt-3 flex items-center gap-2">
                <button
                    type="button"
                    class="rounded-lg bg-brand-500 px-3 py-2 text-sm font-semibold text-white shadow-theme-xs hover:bg-brand-600 disabled:cursor-not-allowed disabled:bg-gray-300"
                    data-pwa-install-button
                    disabled
                >
                    Preparando...
                </button>
                <button
                    type="button"
                    class="rounded-lg px-3 py-2 text-sm font-semibold text-gray-600 hover:bg-gray-100"
                    data-pwa-install-dismiss
                >
                    Agora nao
                </button>
            </div>
        </div>
        <button
            type="button"
            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-gray-500 hover:bg-gray-100 hover:text-gray-700"
            aria-label="Fechar convite de instalacao"
            data-pwa-install-dismiss
        >
            &times;
        </button>
    </div>
</div>
