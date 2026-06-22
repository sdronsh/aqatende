<?php

namespace App\Services\Communication;

use App\Models\CompanySetting;
use App\Services\Licenses\LicenseEnforcer;
use Illuminate\Http\Client\RequestException;
use Throwable;

class WhatsappConnectionChecker
{
    public function __construct(
        private readonly CommunicationClient $communication,
        private readonly LicenseEnforcer $licenseEnforcer,
    ) {
    }

    public function warningForCompanyLogin(int $companyId): ?string
    {
        if (! $this->licenseEnforcer->hasModule($companyId, 'whatsapp')) {
            return null;
        }

        $session = $this->getSessionSnapshot($companyId);
        $uuid = (string) ($session['uuid'] ?? '');

        if ($uuid === '') {
            return null;
        }

        if (! $this->communication->configured()) {
            return 'Nao foi possivel verificar a conexao do WhatsApp agora. Confira a conexao em Configuracoes > WhatsApp.';
        }

        try {
            $freshSession = $this->communication->getWhatsappSessionStatus($uuid);
            $this->storeSessionSnapshot($companyId, $freshSession);

            $status = strtolower((string) ($freshSession['status'] ?? ''));
            if ($status === 'connected') {
                return null;
            }

            return 'A conexao do WhatsApp da empresa caiu. Acesse Configuracoes > WhatsApp para reconectar.';
        } catch (RequestException $exception) {
            if ($exception->response?->status() === 404) {
                $this->clearSessionSnapshot($companyId);

                return 'A sessao WhatsApp salva nao foi encontrada. Acesse Configuracoes > WhatsApp para conectar novamente.';
            }

            return 'Nao foi possivel verificar a conexao do WhatsApp agora. Confira a conexao em Configuracoes > WhatsApp.';
        } catch (Throwable) {
            return 'Nao foi possivel verificar a conexao do WhatsApp agora. Confira a conexao em Configuracoes > WhatsApp.';
        }
    }

    private function getSessionSnapshot(int $companyId): ?array
    {
        $value = CompanySetting::query()
            ->where('company_id', $companyId)
            ->where('key', 'whatsapp_session')
            ->value('value');

        if (! $value) {
            return null;
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : null;
    }

    private function storeSessionSnapshot(int $companyId, array $session): void
    {
        CompanySetting::query()->updateOrCreate(
            ['company_id' => $companyId, 'key' => 'whatsapp_session'],
            ['value' => json_encode($session)]
        );
    }

    private function clearSessionSnapshot(int $companyId): void
    {
        CompanySetting::query()->updateOrCreate(
            ['company_id' => $companyId, 'key' => 'whatsapp_session'],
            ['value' => null]
        );
    }
}
