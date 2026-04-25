<x-app-layout>
    <x-slot name="header">
        <div>
            <div class="text-xs uppercase text-gray-400">Configuracoes</div>
            <h2 class="text-lg font-semibold text-gray-800">Termo de uso</h2>
        </div>
    </x-slot>

    <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-theme-sm space-y-6">
        <div class="text-sm text-gray-600">
            Atualize a versao e o texto do Termo de Uso. Uma nova versao exige novo aceite das clinicas.
        </div>

        <form method="POST" action="{{ route('settings.terms.update') }}" class="space-y-4">
            @csrf
            @method('PUT')

            <div class="grid gap-4 md:grid-cols-12">
                <div class="md:col-span-3">
                    <label class="mb-1 block text-sm font-medium text-gray-700" for="version">Versao</label>
                    <input class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 shadow-theme-xs focus:border-brand-300 focus:outline-none focus:ring-3 focus:ring-brand-500/10" id="version" name="version" value="{{ old('version', $terms?->version ?? '') }}" />
                    @error('version')<div class="text-error-500 text-sm mt-1">{{ $message }}</div>@enderror
                </div>
                <div class="md:col-span-3">
                    <label class="mb-1 block text-sm font-medium text-gray-700" for="effective_at">Vigente desde</label>
                    <input class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 shadow-theme-xs focus:border-brand-300 focus:outline-none focus:ring-3 focus:ring-brand-500/10" id="effective_at" type="date" name="effective_at" value="{{ old('effective_at', optional($terms?->effective_at)->format('Y-m-d')) }}" />
                    @error('effective_at')<div class="text-error-500 text-sm mt-1">{{ $message }}</div>@enderror
                </div>
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700" for="body">Texto do termo</label>
                <textarea class="min-h-[320px] w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 shadow-theme-xs focus:border-brand-300 focus:outline-none focus:ring-3 focus:ring-brand-500/10" id="body" name="body">{{ old('body', $terms?->body ?? '') }}</textarea>
                @error('body')<div class="text-error-500 text-sm mt-1">{{ $message }}</div>@enderror
            </div>

            <div>
                <div class="mb-1 text-sm font-medium text-gray-700">Pre-visualizacao</div>
                <div class="w-full max-w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 overflow-auto overflow-x-hidden">
                    <pre id="terms-preview" class="m-0 max-w-full text-sm text-gray-700" style="white-space: pre-wrap; overflow-wrap: anywhere; word-break: break-word;"></pre>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <button type="submit" class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-semibold text-white shadow-theme-xs hover:bg-brand-600">Salvar</button>
                <a class="rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-theme-xs hover:bg-gray-50" href="{{ route('dashboard') }}">Voltar</a>
            </div>
        </form>
    </div>
</x-app-layout>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const textarea = document.getElementById('body');
        const preview = document.getElementById('terms-preview');
        const sync = () => {
            if (!preview || !textarea) return;
            preview.textContent = textarea.value || '';
        };
        if (textarea) {
            textarea.addEventListener('input', sync);
            sync();
        }
    });
</script>
