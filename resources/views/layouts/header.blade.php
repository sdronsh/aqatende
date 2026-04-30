@php
    $companyId = session('active_company_id');
    $company = $companyId ? \App\Models\Company::find($companyId) : null;
    $companyLogo = $companyId
        ? \App\Models\CompanySetting::where('company_id', $companyId)->where('key', 'logo_path')->value('value')
        : null;
@endphp

<header class="sticky top-0 z-99999 w-full bg-white border-b border-gray-200">
    <div class="flex items-center justify-between px-4 py-3 lg:px-6">
        <div class="flex items-center gap-3">
            <button id="sidebar-toggle" class="flex h-10 w-10 items-center justify-center rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-100">
                <svg width="16" height="12" viewBox="0 0 16 12" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path fill-rule="evenodd" clip-rule="evenodd" d="M0.583252 1C0.583252 0.585788 0.919038 0.25 1.33325 0.25H14.6666C15.0808 0.25 15.4166 0.585786 15.4166 1C15.4166 1.41421 15.0808 1.75 14.6666 1.75L1.33325 1.75C0.919038 1.75 0.583252 1.41422 0.583252 1ZM0.583252 11C0.583252 10.5858 0.919038 10.25 1.33325 10.25L14.6666 10.25C15.0808 10.25 15.4166 10.5858 15.4166 11C15.4166 11.4142 15.0808 11.75 14.6666 11.75L1.33325 11.75C0.919038 11.75 0.583252 11.4142 0.583252 11ZM1.33325 5.25C0.919038 5.25 0.583252 5.58579 0.583252 6C0.583252 6.41421 0.919038 6.75 1.33325 6.75L7.99992 6.75C8.41413 6.75 8.74992 6.41421 8.74992 6C8.74992 5.58579 8.41413 5.25 7.99992 5.25L1.33325 5.25Z" fill="currentColor"/>
                </svg>
            </button>
            <a class="flex items-center gap-2" href="{{ route('dashboard') }}">
                <img class="h-9 w-9 rounded-full object-contain border border-brand-100 bg-white" src="{{ $companyLogo ? asset('storage/'.$companyLogo) : asset('logo.png') }}" alt="AQAtende" />
                <span class="text-sm font-semibold text-gray-800">{{ $company?->name ?? 'AQAtende' }}</span>
            </a>
            <div class="hidden lg:block">
                <input class="h-10 w-full rounded-lg border border-gray-200 bg-transparent px-4 text-sm text-gray-700 focus:border-brand-300 focus:outline-none focus:ring-3 focus:ring-brand-500/10 xl:w-[420px]" placeholder="Buscar..." />
            </div>
        </div>

        <div class="flex items-center gap-4">
            <span class="text-sm text-gray-600">{{ Auth::user()->name }}</span>
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

    <dialog id="support-confirm-dialog" class="m-auto w-full max-w-md rounded-xl border border-gray-200 p-0 shadow-theme-lg">
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

    <dialog id="support-message-dialog" class="m-auto w-full max-w-lg rounded-xl border border-gray-200 p-0 shadow-theme-lg">
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
    });
</script>
