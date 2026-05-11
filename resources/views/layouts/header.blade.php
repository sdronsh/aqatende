@php
    $companyId = session('active_company_id');
    $company = $companyId ? \App\Models\Company::find($companyId) : null;
    $licenseEnforcer = app(\App\Services\Licenses\LicenseEnforcer::class);
    $hasWhatsappModule = $companyId ? $licenseEnforcer->hasModule((int) $companyId, 'whatsapp') : true;
    $showWhatsappOffer = $companyId && ! auth()->user()?->is_platform_admin && ! $hasWhatsappModule;
    $whatsappOfferMessage = 'Tenho interesse em contratar o modulo WhatsApp para a minha empresa.';
    $whatsappOfferUrl = 'https://wa.me/5531993723008?text='.urlencode($whatsappOfferMessage);
    $companyLogo = $companyId
        ? \App\Models\CompanySetting::where('company_id', $companyId)->where('key', 'logo_path')->value('value')
        : null;
@endphp

<header class="sticky top-0 z-99999 w-full bg-white border-b border-gray-200">
    <div class="flex flex-wrap items-center justify-between gap-3 px-4 py-3 lg:flex-nowrap lg:px-6">
        <div class="flex min-w-0 flex-1 items-center gap-3">
            <button id="sidebar-toggle" class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-100">
                <svg width="16" height="12" viewBox="0 0 16 12" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path fill-rule="evenodd" clip-rule="evenodd" d="M0.583252 1C0.583252 0.585788 0.919038 0.25 1.33325 0.25H14.6666C15.0808 0.25 15.4166 0.585786 15.4166 1C15.4166 1.41421 15.0808 1.75 14.6666 1.75L1.33325 1.75C0.919038 1.75 0.583252 1.41422 0.583252 1ZM0.583252 11C0.583252 10.5858 0.919038 10.25 1.33325 10.25L14.6666 10.25C15.0808 10.25 15.4166 10.5858 15.4166 11C15.4166 11.4142 15.0808 11.75 14.6666 11.75L1.33325 11.75C0.919038 11.75 0.583252 11.4142 0.583252 11ZM1.33325 5.25C0.919038 5.25 0.583252 5.58579 0.583252 6C0.583252 6.41421 0.919038 6.75 1.33325 6.75L7.99992 6.75C8.41413 6.75 8.74992 6.41421 8.74992 6C8.74992 5.58579 8.41413 5.25 7.99992 5.25L1.33325 5.25Z" fill="currentColor"/>
                </svg>
            </button>
            <a class="flex min-w-0 items-center gap-2" href="{{ route('dashboard') }}">
                <img class="h-9 w-9 shrink-0 rounded-full object-contain border border-brand-100 bg-white" src="{{ $companyLogo ? asset('storage/'.$companyLogo) : asset('logo.png') }}" alt="AQAtende" />
                <span class="truncate text-sm font-semibold text-gray-800">{{ $company?->name ?? 'AQAtende' }}</span>
            </a>
            <div class="hidden lg:block">
                <input class="h-10 w-full rounded-lg border border-gray-200 bg-transparent px-4 text-sm text-gray-700 focus:border-brand-300 focus:outline-none focus:ring-3 focus:ring-brand-500/10 xl:w-[420px]" placeholder="Buscar..." />
            </div>
        </div>

        <div class="flex shrink-0 items-center gap-2 sm:gap-4">
            <span class="hidden max-w-40 truncate text-sm text-gray-600 sm:inline">{{ Auth::user()->name }}</span>
            <a href="{{ route('profile.edit') }}" class="text-sm text-gray-500 hover:text-gray-700">Perfil</a>
            <button
                type="button"
                class="flex h-8 w-8 items-center justify-center rounded-full border border-gray-200 text-sm font-semibold text-gray-500 hover:border-brand-300 hover:bg-brand-50 hover:text-brand-600"
                title="Enviar duvida/problema ao suporte"
                aria-label="Enviar duvida/problema ao suporte"
                data-open-support-confirm
            >
                ?
            </button>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="text-sm text-red-500 hover:text-red-600">Sair</button>
            </form>
        </div>
    </div>

    @if ($showWhatsappOffer)
        <div
            class="hidden border-t border-emerald-100 bg-emerald-50 px-4 py-3 lg:px-6"
            data-whatsapp-offer
            data-company-id="{{ $companyId }}"
        >
            <div class="mx-auto flex max-w-(--breakpoint-2xl) flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex min-w-0 items-start gap-3">
                    <span class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-emerald-700">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M20 11.5a8 8 0 0 1-11.8 7L4 20l1.5-4.1A8 8 0 1 1 20 11.5z" />
                            <path d="M9 8.5c.2 2.9 2.1 5 5 5.5l1.2-1.2c.2-.2.2-.5 0-.7l-1.1-1.1c-.2-.2-.5-.2-.7 0l-.6.6c-1-.4-1.8-1.2-2.2-2.2l.6-.6c.2-.2.2-.5 0-.7L10.1 7c-.2-.2-.5-.2-.7 0L9 7.4c-.1.2-.1.6 0 1.1z" />
                        </svg>
                    </span>
                    <div class="min-w-0">
                        <div class="text-sm font-semibold text-emerald-900">Modulo WhatsApp disponivel para contratacao</div>
                        <div class="mt-0.5 text-sm text-emerald-800">
                            Sua empresa ainda nao possui o WhatsApp no plano. Ative para automatizar agendamentos, lembretes e atendimento.
                            <a class="font-semibold underline underline-offset-2 hover:text-emerald-950" href="{{ $whatsappOfferUrl }}" target="_blank" rel="noopener noreferrer">
                                Tenho interesse pelo WhatsApp
                            </a>
                            ou entre em contato pelo telefone (31) 99372-3008.
                        </div>
                    </div>
                </div>
                <div class="flex shrink-0 items-center gap-2 self-end sm:self-auto">
                    <a
                        href="{{ $whatsappOfferUrl }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="rounded-lg bg-emerald-600 px-3 py-2 text-sm font-semibold text-white shadow-theme-xs hover:bg-emerald-700"
                    >
                        Tenho interesse
                    </a>
                    <button
                        type="button"
                        class="flex h-9 w-9 items-center justify-center rounded-lg text-emerald-800 hover:bg-emerald-100"
                        aria-label="Fechar aviso do modulo WhatsApp"
                        data-dismiss-whatsapp-offer
                    >
                        &times;
                    </button>
                </div>
            </div>
        </div>
    @endif

    <dialog id="support-confirm-dialog" class="m-auto max-h-[90vh] w-[calc(100%-2rem)] max-w-md overflow-y-auto rounded-xl border border-gray-200 p-0 shadow-theme-lg">
        <div class="flex flex-col gap-4 p-5">
            <div class="text-lg font-semibold text-gray-800">Enviar duvida/problema ao suporte</div>
            <p class="text-sm text-gray-600">
                Deseja enviar uma duvida ou problema para o suporte?
            </p>
            <div class="flex items-center justify-end gap-2 border-t border-gray-100 pt-4">
                <button type="button" id="support-confirm-no" class="rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-theme-xs hover:bg-gray-50">
                    Nao
                </button>
                <button type="button" id="support-confirm-yes" class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-semibold text-white shadow-theme-xs hover:bg-brand-600">
                    Sim
                </button>
            </div>
        </div>
    </dialog>

    <dialog id="support-message-dialog" class="m-auto max-h-[90vh] w-[calc(100%-2rem)] max-w-lg overflow-y-auto rounded-xl border border-gray-200 p-0 shadow-theme-lg">
        <form method="POST" action="{{ route('support.request') }}" class="flex flex-col gap-4 p-5">
            @csrf
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-800">Informacoes para o suporte</h3>
                <button type="button" class="text-gray-500 hover:text-gray-700" data-close-support-message>&times;</button>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700" for="support-message">Mensagem</label>
                <textarea
                    id="support-message"
                    name="message"
                    rows="6"
                    maxlength="5000"
                    required
                    class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 shadow-theme-xs focus:border-brand-300 focus:outline-none focus:ring-3 focus:ring-brand-500/10"
                    placeholder="Descreva o que precisa de suporte..."
                >{{ old('message') }}</textarea>
            </div>
            <div class="flex items-center justify-end gap-2 border-t border-gray-100 pt-4">
                <button type="button" class="rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-theme-xs hover:bg-gray-50" data-close-support-message>
                    Cancelar
                </button>
                <button type="submit" class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-semibold text-white shadow-theme-xs hover:bg-brand-600">
                    Enviar
                </button>
            </div>
        </form>
    </dialog>
</header>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const confirmDialog = document.getElementById('support-confirm-dialog');
        const messageDialog = document.getElementById('support-message-dialog');
        const messageInput = document.getElementById('support-message');
        const confirmNo = document.getElementById('support-confirm-no');
        const confirmYes = document.getElementById('support-confirm-yes');
        const whatsappOffer = document.querySelector('[data-whatsapp-offer]');
        const whatsappOfferStorageKey = whatsappOffer?.dataset.companyId
            ? `aqamed.whatsappOffer.v2.dismissed.${whatsappOffer.dataset.companyId}`
            : null;

        const closeSupportDialogs = () => {
            if (confirmDialog?.open) confirmDialog.close();
            if (messageDialog?.open) messageDialog.close();
        };

        document.querySelectorAll('[data-open-support-confirm]').forEach((button) => {
            button.addEventListener('click', () => {
                confirmDialog?.showModal();
                confirmNo?.focus();
            });
        });

        confirmNo?.addEventListener('click', closeSupportDialogs);

        confirmYes?.addEventListener('click', () => {
            if (confirmDialog?.open) confirmDialog.close();
            messageDialog?.showModal();
            messageInput?.focus();
        });

        messageDialog?.querySelectorAll('[data-close-support-message]').forEach((button) => {
            button.addEventListener('click', closeSupportDialogs);
        });

        confirmDialog?.addEventListener('click', (event) => {
            if (event.target === confirmDialog) {
                closeSupportDialogs();
            }
        });

        messageDialog?.addEventListener('click', (event) => {
            if (event.target === messageDialog) {
                closeSupportDialogs();
            }
        });

        if (whatsappOffer && whatsappOfferStorageKey) {
            if (localStorage.getItem(whatsappOfferStorageKey) === '1') {
                whatsappOffer.remove();
            } else {
                whatsappOffer.classList.remove('hidden');
            }

            whatsappOffer.querySelector('[data-dismiss-whatsapp-offer]')?.addEventListener('click', () => {
                localStorage.setItem(whatsappOfferStorageKey, '1');
                whatsappOffer.remove();
            });
        }
    });
</script>
