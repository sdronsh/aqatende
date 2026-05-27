<x-app-layout>
    <x-slot name="header">
        <div>
            <div class="text-xs uppercase text-gray-400">Configuracoes</div>
            <h2 class="text-lg font-semibold text-gray-800">Agendamento online</h2>
        </div>
    </x-slot>

    <div class="space-y-4">
        @if (session('status'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800 shadow-theme-sm">
                {{ session('status') }}
            </div>
        @endif

        <section class="rounded-xl border border-gray-200 bg-white p-6 shadow-theme-sm">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <div class="text-xs uppercase text-gray-400">Link publico</div>
                    <h3 class="mt-1 text-base font-semibold text-gray-800">{{ $company->name }}</h3>
                    <p class="mt-2 max-w-2xl text-sm text-gray-500">
                        Use este link em redes sociais, WhatsApp, site ou cartao digital. O cliente informa telefone ou nome, escolhe o servico e confirma o horario.
                    </p>
                </div>
                <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-800">Ativo</span>
            </div>

            <div class="mt-5 rounded-lg border border-gray-100 bg-gray-50 p-4">
                <label class="mb-1 block text-xs font-semibold uppercase text-gray-500" for="online_booking_url">Link de agendamento</label>
                <div class="flex flex-col gap-2 sm:flex-row">
                    <input id="online_booking_url" class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700" value="{{ $bookingUrl }}" readonly>
                    <button type="button" class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-600" data-copy-online-booking>
                        Copiar
                    </button>
                </div>
                <p class="mt-2 text-xs text-gray-500">Este link nao expira. Renove apenas se precisar invalidar o link anterior.</p>
            </div>

            <div class="mt-5 flex flex-wrap gap-2">
                <a class="rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50" href="{{ $bookingUrl }}" target="_blank" rel="noopener">
                    Abrir link
                </a>
                <form method="POST" action="{{ route('settings.online-booking.regenerate') }}" onsubmit="return confirm('Renovar o link? O link atual deixara de funcionar.');">
                    @csrf
                    <button type="submit" class="rounded-lg border border-error-200 bg-white px-4 py-2 text-sm font-semibold text-error-700 hover:bg-error-50">
                        Renovar link
                    </button>
                </form>
            </div>
        </section>

        <section class="rounded-xl border border-gray-200 bg-white p-6 shadow-theme-sm">
            <h3 class="text-base font-semibold text-gray-800">Como o cliente sera identificado</h3>
            <div class="mt-4 grid gap-3 md:grid-cols-3">
                <div class="rounded-lg border border-gray-100 bg-gray-50 p-4">
                    <div class="text-sm font-semibold text-gray-800">Com telefone</div>
                    <p class="mt-2 text-sm text-gray-500">Se o telefone ja existir, o agendamento usa o cadastro encontrado.</p>
                </div>
                <div class="rounded-lg border border-gray-100 bg-gray-50 p-4">
                    <div class="text-sm font-semibold text-gray-800">Nome igual</div>
                    <p class="mt-2 text-sm text-gray-500">Se nao houver telefone, mas o nome for exatamente igual, usa o cliente existente.</p>
                </div>
                <div class="rounded-lg border border-gray-100 bg-gray-50 p-4">
                    <div class="text-sm font-semibold text-gray-800">Novo cliente</div>
                    <p class="mt-2 text-sm text-gray-500">Se nao encontrar cadastro, cria um cliente com os dados informados.</p>
                </div>
            </div>
        </section>
    </div>

    <script>
        document.querySelector('[data-copy-online-booking]')?.addEventListener('click', async () => {
            const input = document.getElementById('online_booking_url');
            if (!input) return;
            await navigator.clipboard?.writeText(input.value);
        });
    </script>
</x-app-layout>
