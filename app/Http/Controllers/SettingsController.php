<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\Term;
use App\Services\Communication\CommunicationClient;
use App\Services\Licenses\LicenseClient;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Throwable;

class SettingsController extends Controller
{
    public function license(Request $request): View
    {
        $company = $this->getCompany($request);
        $license = null;

        if (! empty($company->cnpj)) {
            $license = app(LicenseClient::class)->getLicenseByCnpj((string) $company->cnpj);
        }

        return view('settings/license', [
            'company' => $company,
            'license' => is_array($license) ? $license : null,
        ]);
    }

    public function generateLicensePayment(Request $request): RedirectResponse
    {
        $company = $this->getCompany($request);
        $license = null;

        if (! empty($company->cnpj)) {
            $license = app(LicenseClient::class)->getLicenseByCnpj((string) $company->cnpj, false);
        }

        $paymentUrl = $this->resolveLicensePaymentUrl($company, is_array($license) ? $license : null);
        if ($paymentUrl) {
            return redirect()->away($paymentUrl);
        }

        return back()->with(
            'status',
            'Geracao de pagamento preparada. Configure o link vindo da API de licencas ou LICENSES_PAYMENT_URL_TEMPLATE para direcionar o cliente.'
        );
    }

    public function logo(Request $request): View
    {
        $company = $this->getCompany($request);

        return view('settings/logo', [
            'company' => $company,
            'logoPath' => $this->getSetting($company->id, 'logo_path'),
        ]);
    }

    public function whatsapp(Request $request, CommunicationClient $communication): View
    {
        $company = $this->getCompany($request);
        $apiUrl = (string) config('aqamed.communication.api_url', '');
        $session = $this->getWhatsappSessionSnapshot($company->id);
        $activeTab = (string) $request->query('tab', 'templates');
        $allowedTabs = ['templates', 'campanhas', 'fluxo', 'regras', 'conexao'];
        if (! in_array($activeTab, $allowedTabs, true)) {
            $activeTab = 'templates';
        }
        $automation = $this->getWhatsappAutomationSettings($company->id);
        $webhookUrl = rtrim((string) config('app.url'), '/').'/api/whatsapp/webhook';
        $webhookTokenConfigured = (string) config('aqamed.communication.webhook_token', '') !== '';

        return view('settings/whatsapp', [
            'company' => $company,
            'apiUrl' => $apiUrl,
            'apiConfigured' => $communication->configured(),
            'session' => $session,
            'activeTab' => $activeTab,
            'automation' => $automation,
            'webhookUrl' => $webhookUrl,
            'webhookTokenConfigured' => $webhookTokenConfigured,
        ]);
    }

    public function updateWhatsappAutomation(Request $request): RedirectResponse
    {
        $company = $this->getCompany($request);
        $data = $request->validate([
            'inactive_days' => ['nullable', 'integer', 'min:1', 'max:365'],
            'send_to_all' => ['nullable', 'boolean'],
            'send_to_inactive' => ['nullable', 'boolean'],
            'bot_enabled' => ['nullable', 'boolean'],
            'bot_allow_any_professional' => ['nullable', 'boolean'],
            'bot_confirmation_template' => ['nullable', 'string', 'max:280'],
            'template_welcome' => ['nullable', 'string', 'max:2000'],
            'template_inactive' => ['nullable', 'string', 'max:2000'],
            'template_birthday' => ['nullable', 'string', 'max:2000'],
        ]);

        $tab = (string) $request->input('tab', 'templates');
        $payload = $this->getWhatsappAutomationSettings($company->id);

        if ($tab === 'templates') {
            $payload['templates'] = array_replace($payload['templates'] ?? [], [
                'welcome' => (string) ($data['template_welcome'] ?? ''),
                'inactive' => (string) ($data['template_inactive'] ?? ''),
                'birthday' => (string) ($data['template_birthday'] ?? ''),
            ]);
        }

        if ($tab === 'campanhas') {
            $payload['campaigns'] = array_replace($payload['campaigns'] ?? [], [
                'send_to_all' => (bool) ($data['send_to_all'] ?? false),
                'send_to_inactive' => (bool) ($data['send_to_inactive'] ?? false),
                'inactive_days' => (int) ($data['inactive_days'] ?? 30),
            ]);
        }

        if ($tab === 'fluxo') {
            $payload['flow'] = array_replace($payload['flow'] ?? [], [
                'bot_enabled' => (bool) ($data['bot_enabled'] ?? false),
                'bot_allow_any_professional' => (bool) ($data['bot_allow_any_professional'] ?? true),
                'bot_confirmation_template' => (string) ($data['bot_confirmation_template'] ?? ''),
            ]);
        }

        $payload['rules'] = [
            'steps' => [
                'ask_booking' => true,
                'select_service' => true,
                'select_professional' => true,
                'select_time' => true,
                'auto_schedule' => true,
            ],
        ];

        $this->storeSetting($company->id, 'whatsapp_automation', json_encode($payload));

        return redirect()
            ->route('settings.whatsapp', ['tab' => (string) $request->input('tab', 'templates')])
            ->with('status', 'Configuracoes de automacao salvas.');
    }

    public function generateWhatsappQr(Request $request, CommunicationClient $communication): RedirectResponse
    {
        $company = $this->getCompany($request);
        $tab = $this->resolveWhatsappTab($request, 'conexao');

        if (! $communication->configured()) {
            return redirect()->route('settings.whatsapp', ['tab' => $tab])
                ->withErrors(['whatsapp' => 'API de comunicacao nao configurada. Verifique COMMUNICATION_API_URL e COMMUNICATION_API_TOKEN.']);
        }

        try {
            $currentSession = $this->getWhatsappSessionSnapshot($company->id);
            $uuid = (string) ($currentSession['uuid'] ?? '');

            if ($uuid !== '') {
                try {
                    $session = $communication->getWhatsappSessionQr($uuid);
                } catch (RequestException $exception) {
                    if ($exception->response?->status() !== 404) {
                        throw $exception;
                    }

                    $session = $this->createWhatsappSession($communication, $company);
                }
            } else {
                $session = $this->createWhatsappSession($communication, $company);
            }

            $this->storeWhatsappSessionSnapshot($company->id, $session);

            return redirect()->route('settings.whatsapp', ['tab' => $tab])->with('status', 'QR Code atualizado.');
        } catch (Throwable $exception) {
            return redirect()->route('settings.whatsapp', ['tab' => $tab])
                ->withErrors(['whatsapp' => 'Nao foi possivel gerar o QR Code: '.$exception->getMessage()]);
        }
    }

    public function refreshWhatsappStatus(Request $request, CommunicationClient $communication): RedirectResponse
    {
        $company = $this->getCompany($request);
        $tab = $this->resolveWhatsappTab($request, 'conexao');
        $session = $this->getWhatsappSessionSnapshot($company->id);
        $uuid = (string) ($session['uuid'] ?? '');

        if (! $communication->configured()) {
            return redirect()->route('settings.whatsapp', ['tab' => $tab])
                ->withErrors(['whatsapp' => 'API de comunicacao nao configurada. Verifique COMMUNICATION_API_URL e COMMUNICATION_API_TOKEN.']);
        }

        if ($uuid === '') {
            return redirect()->route('settings.whatsapp', ['tab' => $tab])
                ->withErrors(['whatsapp' => 'Gere o QR Code antes de atualizar o status.']);
        }

        try {
            $session = $communication->getWhatsappSessionStatus($uuid);
            $this->storeWhatsappSessionSnapshot($company->id, $session);

            return redirect()->route('settings.whatsapp', ['tab' => $tab])->with('status', 'Status atualizado.');
        } catch (Throwable $exception) {
            return redirect()->route('settings.whatsapp', ['tab' => $tab])
                ->withErrors(['whatsapp' => 'Nao foi possivel atualizar o status: '.$exception->getMessage()]);
        }
    }

    public function generateWhatsappPairingCode(Request $request, CommunicationClient $communication): RedirectResponse
    {
        $company = $this->getCompany($request);
        $tab = $this->resolveWhatsappTab($request, 'conexao');

        $data = $request->validate([
            'phone' => ['required', 'string', 'max:20'],
            'phone_variant' => ['nullable', 'in:with_ninth,without_ninth'],
        ]);
        $phone = $this->normalizeBrazilPhone($data['phone'], $data['phone_variant'] ?? 'with_ninth');

        if ($phone === null) {
            return redirect()->route('settings.whatsapp', ['tab' => $tab])
                ->withErrors(['whatsapp' => 'Informe um telefone brasileiro valido com DDD. Exemplo: (31) 99999-9999.'])
                ->withInput();
        }

        if (! $communication->configured()) {
            return redirect()->route('settings.whatsapp', ['tab' => $tab])
                ->withErrors(['whatsapp' => 'API de comunicacao nao configurada. Verifique COMMUNICATION_API_URL e COMMUNICATION_API_TOKEN.']);
        }

        try {
            $session = $this->getWhatsappSessionSnapshot($company->id);
            $uuid = (string) ($session['uuid'] ?? '');

            if ($uuid === '') {
                $session = $this->createWhatsappSession($communication, $company);
                $uuid = (string) ($session['uuid'] ?? '');
            }

            if ($uuid === '') {
                return redirect()->route('settings.whatsapp', ['tab' => $tab])
                    ->withErrors(['whatsapp' => 'Nao foi possivel identificar a sessao WhatsApp criada.']);
            }

            try {
                $session = $communication->getWhatsappPairingCode($uuid, $phone);
            } catch (RequestException $exception) {
                if ($exception->response?->status() !== 404) {
                    throw $exception;
                }

                $session = $this->createWhatsappSession($communication, $company);
                $uuid = (string) ($session['uuid'] ?? '');

                if ($uuid === '') {
                    return redirect()->route('settings.whatsapp', ['tab' => $tab])
                        ->withErrors(['whatsapp' => 'Nao foi possivel identificar a sessao WhatsApp criada.']);
                }

                $session = $communication->getWhatsappPairingCode($uuid, $phone);
            }

            $this->storeWhatsappSessionSnapshot($company->id, $session);

            return redirect()->route('settings.whatsapp', ['tab' => $tab])->with('status', 'Codigo de pareamento gerado.');
        } catch (Throwable $exception) {
            return redirect()->route('settings.whatsapp', ['tab' => $tab])
                ->withErrors(['whatsapp' => 'Nao foi possivel gerar o codigo: '.$exception->getMessage()]);
        }
    }

    public function resetWhatsappSession(Request $request, CommunicationClient $communication): RedirectResponse
    {
        $company = $this->getCompany($request);
        $tab = $this->resolveWhatsappTab($request, 'conexao');
        $session = $this->getWhatsappSessionSnapshot($company->id);
        $uuid = (string) ($session['uuid'] ?? '');

        if ($uuid !== '' && $communication->configured()) {
            try {
                $communication->deleteWhatsappSession($uuid);
            } catch (Throwable) {
                // A sessao local sera removida mesmo que ela ja nao exista no servico de comunicacao.
            }
        }

        $this->storeSetting($company->id, 'whatsapp_session', null);

        return redirect()->route('settings.whatsapp', ['tab' => $tab])->with('status', 'Sessao WhatsApp reiniciada.');
    }

    public function updateLogo(Request $request): RedirectResponse
    {
        $company = $this->getCompany($request);

        $data = $request->validate([
            'logo' => ['nullable', 'file', 'mimes:png,jpg,jpeg,svg', 'max:3072'],
            'remove_logo' => ['nullable', 'boolean'],
        ]);

        if ($request->boolean('remove_logo')) {
            $this->storeSetting($company->id, 'logo_path', null);
            return redirect()->route('settings.logo')->with('status', 'Logo removida.');
        }

        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('company_logos', 'public');
            $this->storeSetting($company->id, 'logo_path', $path);
        }

        return redirect()->route('settings.logo')->with('status', 'Logo atualizada.');
    }

    public function terms(Request $request): View
    {
        $this->ensurePlatformAdmin($request);
        $terms = Term::where('key', 'usage')->first();

        return view('settings/terms', [
            'terms' => $terms,
        ]);
    }

    public function updateTerms(Request $request): RedirectResponse
    {
        $this->ensurePlatformAdmin($request);

        $data = $request->validate([
            'version' => ['required', 'string', 'max:20'],
            'effective_at' => ['nullable', 'date'],
            'body' => ['required', 'string'],
        ]);

        Term::updateOrCreate(
            ['key' => 'usage'],
            [
                'version' => $data['version'],
                'effective_at' => $data['effective_at'] ?? null,
                'body' => $data['body'],
                'updated_by_user_id' => $request->user()?->id,
            ]
        );

        return redirect()->route('settings.terms.edit')->with('status', 'Termo de uso atualizado.');
    }

    private function ensurePlatformAdmin(Request $request): void
    {
        if (! $request->user()?->is_platform_admin) {
            abort(403);
        }
    }

    private function getCompany(Request $request): Company
    {
        $companyId = $request->session()->get('active_company_id');
        if (! $companyId) {
            abort(403);
        }

        return Company::findOrFail($companyId);
    }

    private function getSetting(int $companyId, string $key): ?string
    {
        return CompanySetting::where('company_id', $companyId)->where('key', $key)->value('value');
    }

    private function storeSetting(int $companyId, string $key, ?string $value): void
    {
        CompanySetting::updateOrCreate(
            ['company_id' => $companyId, 'key' => $key],
            ['value' => $value]
        );
    }

    private function getWhatsappSessionSnapshot(int $companyId): ?array
    {
        $value = $this->getSetting($companyId, 'whatsapp_session');

        if (! $value) {
            return null;
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : null;
    }

    private function storeWhatsappSessionSnapshot(int $companyId, array $session): void
    {
        $this->storeSetting($companyId, 'whatsapp_session', json_encode($session));
    }

    private function getWhatsappAutomationSettings(int $companyId): array
    {
        $defaults = [
            'templates' => [
                'welcome' => 'Oi, {nome}! Como posso ajudar no seu agendamento hoje?',
                'inactive' => 'Oi, {nome}! Sentimos sua falta. Quer agendar um novo horario?',
                'birthday' => 'Feliz aniversario, {nome}! Temos condicoes especiais para voce.',
            ],
            'campaigns' => [
                'send_to_all' => false,
                'send_to_inactive' => true,
                'inactive_days' => 30,
            ],
            'flow' => [
                'bot_enabled' => false,
                'bot_allow_any_professional' => true,
                'bot_confirmation_template' => 'Perfeito, {nome}. Seu horario foi confirmado para {data_hora}.',
            ],
            'rules' => [
                'steps' => [
                    'ask_booking' => true,
                    'select_service' => true,
                    'select_professional' => true,
                    'select_time' => true,
                    'auto_schedule' => true,
                ],
            ],
        ];

        $raw = $this->getSetting($companyId, 'whatsapp_automation');
        if (! $raw) {
            return $defaults;
        }

        $decoded = json_decode($raw, true);
        if (! is_array($decoded)) {
            return $defaults;
        }

        return array_replace_recursive($defaults, $decoded);
    }

    private function createWhatsappSession(CommunicationClient $communication, Company $company): array
    {
        return $communication->createWhatsappSession([
            'system_slug' => 'aqatende',
            'external_tenant_id' => (string) $company->id,
            'external_unit_id' => null,
            'name' => 'WhatsApp '.$company->id,
            'callback_base_url' => config('app.url'),
        ]);
    }

    private function normalizeBrazilPhone(string $phone, string $variant = 'with_ninth'): ?string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if (str_starts_with($digits, '55') && strlen($digits) > 11) {
            $digits = substr($digits, 2);
        }

        if (strlen($digits) === 11 && $variant === 'without_ninth' && $digits[2] === '9') {
            $digits = substr($digits, 0, 2).substr($digits, 3);
        }

        if (strlen($digits) === 10 || strlen($digits) === 11) {
            return '55'.$digits;
        }

        return null;
    }

    private function resolveLicensePaymentUrl(Company $company, ?array $license): ?string
    {
        $billing = is_array($license['billing'] ?? null) ? $license['billing'] : [];
        $candidates = [
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

        foreach ($candidates as $candidate) {
            if (is_string($candidate) && filter_var($candidate, FILTER_VALIDATE_URL)) {
                return $candidate;
            }
        }

        $template = (string) config('aqamed.license.payment_url_template', '');
        if ($template === '') {
            return null;
        }

        $url = str_replace(
            ['{company_id}', '{cnpj}', '{license_code}'],
            [(string) $company->id, preg_replace('/\D/', '', (string) $company->cnpj), (string) $company->license_code],
            $template
        );

        return filter_var($url, FILTER_VALIDATE_URL) ? $url : null;
    }

    private function resolveWhatsappTab(Request $request, string $default = 'conexao'): string
    {
        $tab = (string) $request->input('tab', $default);
        $allowedTabs = ['templates', 'campanhas', 'fluxo', 'regras', 'conexao'];

        return in_array($tab, $allowedTabs, true) ? $tab : $default;
    }
}
