<?php

namespace App\Services\Licenses;

use App\Models\Clinic;
use App\Models\Company;
use App\Models\Unit;

class LicenseEnforcer
{
    public function __construct(private LicenseClient $client)
    {
    }

    public function canAccessSystem(int $companyId): ?string
    {
        return $this->canAccessSystemInternal($companyId, true);
    }

    public function canAccessSystemRealtime(int $companyId): ?string
    {
        return $this->canAccessSystemInternal($companyId, false);
    }

    private function canAccessSystemInternal(int $companyId, bool $useCache): ?string
    {
        $company = Company::find($companyId);
        if (! $company || ! $company->cnpj) {
            return null;
        }

        $license = $this->client->getLicenseByCnpj($company->cnpj, $useCache);
        if (! is_array($license)) {
            return null;
        }

        if (($license['has_access'] ?? true) === false) {
            return $this->extractBlockReason($license) ?? 'Acesso bloqueado pela licenca da empresa.';
        }

        $status = strtolower((string) ($license['status'] ?? 'active'));
        if ($status !== '' && $status !== 'active') {
            return $this->extractBlockReason($license) ?? 'Licenca da empresa inativa ou bloqueada.';
        }

        $billing = $license['billing'] ?? null;
        if (is_array($billing)) {
            if (($billing['has_access'] ?? true) === false) {
                return $this->extractBlockReason($license) ?? $this->extractBlockReason($billing) ?? 'Acesso bloqueado por pendencia financeira.';
            }

            $billingStatus = strtolower((string) ($billing['status'] ?? ''));
            if (in_array($billingStatus, ['suspended', 'blocked', 'blocked_financial'], true)) {
                return $this->extractBlockReason($license) ?? $this->extractBlockReason($billing) ?? 'Acesso bloqueado por pendencia financeira.';
            }
        }

        return null;
    }

    public function canCreateUser(int $companyId): ?string
    {
        $accessError = $this->canAccessSystem($companyId);
        if ($accessError) {
            return $accessError;
        }

        $company = Company::find($companyId);
        if (! $company || ! $company->cnpj) {
            return null;
        }

        $license = $this->client->getLicenseByCnpj($company->cnpj);
        $license = is_array($license) ? $license : [];
        $limit = $this->intOrDefault($license['user_limit'] ?? null, 'user_limit');
        if ($limit && $company->users()->count() >= $limit) {
            return "Limite de usuarios atingido ({$limit}).";
        }

        return null;
    }

    public function canCreateClinic(int $companyId): ?string
    {
        return null;
    }

    public function canCreateUnit(int $companyId): ?string
    {
        return null;
    }

    private function intOrDefault(?int $value, string $key): int
    {
        if ($value !== null && $value > 0) {
            return (int) $value;
        }

        return (int) config("aqamed.license.defaults.{$key}");
    }

    private function extractBlockReason(array $payload): ?string
    {
        $statusLabel = trim((string) ($payload['status_label'] ?? ''));
        if ($statusLabel !== '') {
            $normalized = strtolower($statusLabel);
            if (str_contains($normalized, 'inadimpl')
                || str_contains($normalized, 'finance')
                || str_contains($normalized, 'cobranc')
                || str_contains($normalized, 'suspend')
                || str_contains($normalized, 'bloquead')) {
                return $this->supportMessage();
            }

            return $statusLabel;
        }

        $status = strtolower((string) ($payload['status'] ?? ''));
        if ($status === 'blocked_financial') {
            return $this->supportMessage();
        }

        return null;
    }

    private function supportMessage(): string
    {
        return 'A licenca da empresa esta com restricao de acesso. Entre em contato com o suporte.';
    }
}
