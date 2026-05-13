<div data-pwa-offline-guard class="pointer-events-none fixed inset-x-4 bottom-4 z-999999 flex flex-col items-center gap-3 sm:items-end" style="z-index: 1000000;">
    <div data-pwa-offline-banner class="pointer-events-auto hidden w-full max-w-md rounded-lg border border-warning-200 bg-warning-50 px-4 py-3 text-sm text-warning-800 shadow-lg" role="status" aria-live="polite">
        <div class="flex items-start justify-between gap-3">
            <div>
                <div class="font-semibold">Sem conexao</div>
                <div class="mt-0.5">Voce pode visualizar a tela atual, mas acoes como salvar, excluir ou gerar registros ficam bloqueadas ate a conexao voltar.</div>
            </div>
            <button type="button" class="text-warning-800/70 hover:text-warning-900" data-pwa-offline-dismiss>&times;</button>
        </div>
    </div>

    <div data-pwa-online-banner class="pointer-events-auto hidden w-full max-w-md rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 shadow-lg" role="status" aria-live="polite">
        Conexao restabelecida.
    </div>

    <div data-pwa-action-blocked class="pointer-events-auto hidden w-full max-w-md rounded-lg border border-error-200 bg-error-50 px-4 py-3 text-sm text-error-700 shadow-lg" role="alert">
        Esta acao precisa de internet. Verifique a conexao e tente novamente.
    </div>

    <div data-pwa-update-banner class="pointer-events-auto hidden w-full max-w-md rounded-lg border border-brand-200 bg-brand-50 px-4 py-3 text-sm text-brand-800 shadow-lg" role="status" aria-live="polite">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <div class="font-semibold">Atualizacao disponivel</div>
                <div class="mt-0.5">Recarregue para usar a versao mais recente do AQAtende.</div>
            </div>
            <button type="button" class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700" data-pwa-update-reload>
                Atualizar
            </button>
        </div>
    </div>
</div>
