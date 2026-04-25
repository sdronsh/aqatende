<x-app-layout>
    <x-slot name="header">
        <div>
            <div class="text-xs uppercase text-gray-400">Configuracoes</div>
            <h2 class="text-lg font-semibold text-gray-800">Licenca</h2>
        </div>
    </x-slot>

    @php
        $cnpjDigits = preg_replace('/\D/', '', (string) ($company->cnpj ?? ''));
        $cnpjFormatted = strlen($cnpjDigits) === 14
            ? substr($cnpjDigits, 0, 2).'.'.substr($cnpjDigits, 2, 3).'.'.substr($cnpjDigits, 5, 3).'/'.substr($cnpjDigits, 8, 4).'-'.substr($cnpjDigits, 12, 2)
            : ($company->cnpj ?? '-');

        $status = strtolower((string) ($license['status'] ?? 'indisponivel'));
        $statusLabel = (string) ($license['status_label'] ?? ucfirst(str_replace('_', ' ', $status)));
        $hasAccess = $license['has_access'] ?? null;

        $billing = is_array($license['billing'] ?? null) ? $license['billing'] : [];
        $billingStatus = strtolower((string) ($billing['status'] ?? 'indisponivel'));
        $billingStatusLabel = (string) ($billing['status_label'] ?? ucfirst(str_replace('_', ' ', $billingStatus)));
        $billingHasAccess = $billing['has_access'] ?? null;

        $licenseChip = $status === 'active' ? 'bg-emerald-100 text-emerald-800' : 'bg-error-100 text-error-700';
        $accessChip = $hasAccess === null
            ? 'bg-gray-100 text-gray-700'
            : ($hasAccess === false ? 'bg-error-100 text-error-700' : 'bg-emerald-100 text-emerald-800');
        $accessText = $hasAccess === null ? 'Indisponivel' : ($hasAccess === false ? 'Bloqueado' : 'Liberado');
        $billingChip = in_array($billingStatus, ['active', 'paid'], true) ? 'bg-emerald-100 text-emerald-800' : 'bg-warning-100 text-warning-800';
        $billingAccessChip = $billingHasAccess === null
            ? 'bg-gray-100 text-gray-700'
            : ($billingHasAccess === false ? 'bg-error-100 text-error-700' : 'bg-emerald-100 text-emerald-800');
        $billingAccessText = $billingHasAccess === null ? 'Indisponivel' : ($billingHasAccess === false ? 'Bloqueado' : 'Liberado');
    @endphp

    <div class="space-y-4">
        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-theme-sm">
            <div class="text-sm font-semibold text-gray-800">{{ $company->name }}</div>
            <div class="mt-1 text-xs text-gray-500">CNPJ: {{ $cnpjFormatted }}</div>
        </div>

        @if (! $license)
            <div class="rounded-xl border border-warning-200 bg-warning-50 p-4 text-sm text-warning-800 shadow-theme-sm">
                Nao foi possivel consultar os dados da licenca no momento. Verifique conectividade com a API de licencas e o CNPJ da empresa ativa.
            </div>
        @else
            <div class="grid gap-4 md:grid-cols-12">
                <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-theme-sm md:col-span-6">
                    <div class="text-xs uppercase text-gray-400">Licenca</div>
                    <div class="mt-2 text-sm text-gray-600">Status</div>
                    <div class="mt-1 inline-flex rounded-full px-2 py-1 text-xs font-semibold {{ $licenseChip }}">{{ $statusLabel }}</div>

                    <div class="mt-4 text-sm text-gray-600">Acesso ao sistema</div>
                    <div class="mt-1 inline-flex rounded-full px-2 py-1 text-xs font-semibold {{ $accessChip }}">
                        {{ $accessText }}
                    </div>
                </div>

                <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-theme-sm md:col-span-6">
                    <div class="text-xs uppercase text-gray-400">Financeiro</div>
                    <div class="mt-2 text-sm text-gray-600">Status de cobranca</div>
                    <div class="mt-1 inline-flex rounded-full px-2 py-1 text-xs font-semibold {{ $billingChip }}">{{ $billingStatusLabel }}</div>

                    <div class="mt-4 text-sm text-gray-600">Acesso por financeiro</div>
                    <div class="mt-1 inline-flex rounded-full px-2 py-1 text-xs font-semibold {{ $billingAccessChip }}">
                        {{ $billingAccessText }}
                    </div>
                </div>

                <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-theme-sm md:col-span-12">
                    <div class="text-xs uppercase text-gray-400">Limites</div>
                    <div class="mt-3 grid gap-3 md:grid-cols-3">
                        <div class="rounded-lg border border-gray-100 bg-gray-50 p-3">
                            <div class="text-xs text-gray-500">Usuarios</div>
                            <div class="mt-1 text-lg font-semibold text-gray-800">{{ $license['user_limit'] ?? '-' }}</div>
                        </div>
                        <div class="rounded-lg border border-gray-100 bg-gray-50 p-3">
                            <div class="text-xs text-gray-500">Clinicas</div>
                            <div class="mt-1 text-lg font-semibold text-gray-800">{{ $license['clinic_limit'] ?? '-' }}</div>
                        </div>
                        <div class="rounded-lg border border-gray-100 bg-gray-50 p-3">
                            <div class="text-xs text-gray-500">Unidades</div>
                            <div class="mt-1 text-lg font-semibold text-gray-800">{{ $license['unit_limit'] ?? '-' }}</div>
                        </div>
                    </div>
                </div>

                <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-theme-sm md:col-span-12">
                    <div class="text-xs uppercase text-gray-400">Dados recebidos da API</div>
                    <pre class="mt-3 max-h-80 overflow-auto rounded-lg border border-gray-200 bg-gray-50 p-3 text-xs text-gray-700">{{ json_encode($license, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</pre>
                </div>
            </div>
        @endif
    </div>
</x-app-layout>
