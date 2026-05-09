<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-lg font-semibold text-gray-800">Editar cliente</h2>
            <form method="POST" action="{{ route('patients.booking-link', $patient) }}">
                @csrf
                <button class="rounded-lg border border-success-600 px-4 py-2 text-sm font-semibold text-success-700 shadow-theme-xs hover:bg-success-50" type="submit">Gerar link de agendamento</button>
            </form>
        </div>
    </x-slot>

    @if (session('booking_link'))
        <div class="mb-4 rounded-xl border border-brand-200 bg-brand-50 p-4 text-sm text-brand-900 shadow-theme-sm">
            <div class="font-semibold">Link de agendamento gerado</div>
            <div class="mt-2 flex flex-col gap-2 sm:flex-row">
                <input id="booking-link-input" class="w-full rounded-lg border border-brand-200 bg-white px-3 py-2 text-sm text-gray-700" value="{{ session('booking_link') }}" readonly onclick="this.select()" />
                <a class="inline-flex items-center justify-center rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700" href="{{ session('booking_link') }}" target="_blank" rel="noopener">Abrir</a>
                <button class="inline-flex items-center justify-center rounded-lg border border-brand-600 bg-white px-4 py-2 text-sm font-semibold text-brand-700 hover:bg-white/70" type="button" data-copy-booking-link data-target="booking-link-input">Copiar</button>
            </div>
            <p class="mt-2 text-xs text-brand-700">Copie este link e envie para o cliente pelo WhatsApp. Ele expira em 7 dias ou apos o primeiro agendamento.</p>
        </div>
    @endif

    <form method="POST" action="{{ route('patients.update', $patient) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        <div class="rounded-xl border border-gray-200 bg-white shadow-theme-sm">
            <div class="p-4 md:p-6">
                @include('patients._form', ['patient' => $patient])
            </div>
            <div class="sticky bottom-0 z-20 grid gap-2 border-t border-gray-100 bg-gray-50 px-4 py-3 shadow-[0_-8px_20px_-18px_rgba(16,24,40,0.45)] sm:flex sm:flex-wrap sm:items-center md:px-6">
                <button class="inline-flex w-full items-center justify-center rounded-lg bg-brand-500 px-4 py-2 text-sm font-semibold text-white shadow-theme-xs transition hover:bg-brand-600 sm:w-auto" type="submit">Salvar</button>
                <a class="inline-flex w-full items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-theme-xs transition hover:bg-gray-50 sm:w-auto" href="{{ route('patients.index') }}">Voltar</a>
            </div>
        </div>
    </form>

    <script>
        document.querySelectorAll('[data-copy-booking-link]').forEach((button) => {
            button.addEventListener('click', async () => {
                const input = document.getElementById(button.dataset.target);
                if (! input) return;

                try {
                    await navigator.clipboard.writeText(input.value);
                    button.textContent = 'Copiado';
                } catch (_) {
                    input.focus();
                    input.select();
                    document.execCommand('copy');
                    button.textContent = 'Copiado';
                }

                setTimeout(() => {
                    button.textContent = 'Copiar';
                }, 1800);
            });
        });
    </script>
</x-app-layout>
