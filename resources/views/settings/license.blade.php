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
        $monthlyDueRaw = $billing['oldest_unpaid_due_date'] ?? $billing['due_date'] ?? $billing['next_due_date'] ?? $billing['expires_at'] ?? $billing['valid_until'] ?? null;
        $monthlyReference = $billing['oldest_unpaid_reference'] ?? $billing['reference'] ?? null;
        $monthlyDueDate = null;
        if ($monthlyDueRaw) {
            try {
                $monthlyDueDate = \Illuminate\Support\Carbon::parse($monthlyDueRaw)->startOfDay();
            } catch (\Throwable $e) {
                $monthlyDueDate = null;
            }
        }
        $amountCents = $billing['oldest_unpaid_amount_cents'] ?? $billing['monthly_amount_cents'] ?? $billing['amount_cents'] ?? $billing['value_cents'] ?? null;
        $amountValue = $billing['oldest_unpaid_amount'] ?? $billing['monthly_amount'] ?? $billing['amount'] ?? $billing['value'] ?? null;
        $monthlyAmount = is_numeric($amountCents)
            ? 'R$ '.number_format(((int) $amountCents) / 100, 2, ',', '.')
            : (is_numeric($amountValue) ? 'R$ '.number_format((float) $amountValue, 2, ',', '.') : ($billing['amount_label'] ?? '-'));
        $hasOpenMonthlyCharge = ! empty($billing['oldest_unpaid_due_date']) || ! empty($billing['oldest_unpaid_reference']);
        $isMonthlyOverdue = $monthlyDueDate && $monthlyDueDate->lt(now()->startOfDay()) && $hasOpenMonthlyCharge;
        $isMonthlyDueToday = $monthlyDueDate && $monthlyDueDate->isSameDay(now());
        $monthlyStatusText = $monthlyDueDate
            ? ($isMonthlyOverdue ? 'Mensalidade vencida' : ($isMonthlyDueToday ? 'Mensalidade vence hoje' : ($hasOpenMonthlyCharge ? 'Mensalidade a vencer' : 'Proximo vencimento')))
            : 'Mensalidade indisponivel';
        $monthlyChip = $isMonthlyOverdue
            ? 'bg-error-100 text-error-700'
            : ($isMonthlyDueToday ? 'bg-warning-100 text-warning-800' : 'bg-emerald-100 text-emerald-800');
        $billingPaymentLinks = [
            $billing['oldest_unpaid_payment_url'] ?? null,
            $billing['payment_url'] ?? null,
            $billing['payment_link'] ?? null,
            $billing['checkout_url'] ?? null,
            $billing['invoice_url'] ?? null,
            $billing['mercado_pago_url'] ?? null,
            $billing['init_point'] ?? null,
            $license['payment_url'] ?? null,
            $license['payment_link'] ?? null,
            $license['checkout_url'] ?? null,
            $license['invoice_url'] ?? null,
        ];
        $hasPaymentLink = collect($billingPaymentLinks)->contains(fn ($link) => is_string($link) && filter_var($link, FILTER_VALIDATE_URL));
        $hasPaymentTemplate = filled(config('aqamed.license.payment_url_template'));

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
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <div class="text-xs uppercase text-gray-400">Mensalidade</div>
                            <div class="mt-2 inline-flex rounded-full px-2 py-1 text-xs font-semibold {{ $monthlyChip }}">{{ $monthlyStatusText }}</div>
                            <div class="mt-4 grid gap-3 sm:grid-cols-2">
                                <div class="rounded-lg border border-gray-100 bg-gray-50 p-3">
                                    <div class="text-xs text-gray-500">Vencimento</div>
                                    <div class="mt-1 text-lg font-semibold text-gray-800">
                                        {{ $monthlyDueDate ? $monthlyDueDate->format('d/m/Y') : '-' }}
                                    </div>
                                    @if ($monthlyReference)
                                        <div class="mt-1 text-xs text-gray-500">Referencia {{ $monthlyReference }}</div>
                                    @endif
                                </div>
                                <div class="rounded-lg border border-gray-100 bg-gray-50 p-3">
                                    <div class="text-xs text-gray-500">Valor</div>
                                    <div class="mt-1 text-lg font-semibold text-gray-800">{{ $monthlyAmount }}</div>
                                </div>
                            </div>
                            <div class="mt-3 text-xs text-gray-500">
                                @if ($hasPaymentLink)
                                    Link de pagamento recebido da API de licencas.
                                @elseif ($hasPaymentTemplate)
                                    Link de pagamento sera montado pelo template configurado.
                                @else
                                    Integração pronta para receber link do Mercado Pago ou outro provedor.
                                @endif
                            </div>
                        </div>
                        <form method="POST" action="{{ route('settings.license.payment') }}">
                            @csrf
                            <button class="inline-flex rounded-lg bg-brand-500 px-4 py-2 text-sm font-semibold text-white shadow-theme-xs hover:bg-brand-600" type="submit">
                                Gerar pagamento
                            </button>
                        </form>
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
                            <div class="text-xs text-gray-500">Empresas</div>
                            <div class="mt-1 text-lg font-semibold text-gray-800">{{ $license['clinic_limit'] ?? '-' }}</div>
                        </div>
                        <div class="rounded-lg border border-gray-100 bg-gray-50 p-3">
                            <div class="text-xs text-gray-500">Unidades</div>
                            <div class="mt-1 text-lg font-semibold text-gray-800">{{ $license['unit_limit'] ?? '-' }}</div>
                        </div>
                    </div>
                </div>

                @if (auth()->user()?->is_platform_admin)
                    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-theme-sm md:col-span-12">
                        <div class="text-xs uppercase text-gray-400">Dados recebidos da API</div>
                        <pre class="mt-3 max-h-80 overflow-auto rounded-lg border border-gray-200 bg-gray-50 p-3 text-xs text-gray-700">{{ json_encode($license, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</pre>
                    </div>
                @endif
            </div>
        @endif
    </div>
</x-app-layout>
