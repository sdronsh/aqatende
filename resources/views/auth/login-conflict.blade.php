<x-guest-layout>
    <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-theme-sm">
        <h2 class="text-lg font-semibold text-gray-800">Sessão ativa detectada</h2>
        <p class="mt-2 text-sm text-gray-600">
            Este usuário já está logado em outro local. Deseja derrubar a sessão anterior e continuar?
        </p>

        <div class="mt-6 flex items-center gap-2">
            <form method="POST" action="{{ route('login.conflict.confirm') }}">
                @csrf
                <button class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-semibold text-white shadow-theme-xs hover:bg-brand-600" type="submit">
                    Derrubar e entrar
                </button>
            </form>
            <form method="POST" action="{{ route('login.conflict.cancel') }}">
                @csrf
                <button class="rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-theme-xs hover:bg-gray-50" type="submit">
                    Cancelar
                </button>
            </form>
        </div>
    </div>
</x-guest-layout>
