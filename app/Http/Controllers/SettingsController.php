<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\Term;
use App\Services\Communication\CommunicationClient;
use App\Services\Licenses\LicenseClient;
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

        return view('settings/whatsapp', [
            'company' => $company,
            'apiUrl' => $apiUrl,
            'apiConfigured' => $communication->configured(),
            'session' => $session,
        ]);
    }

    public function generateWhatsappQr(Request $request, CommunicationClient $communication): RedirectResponse
    {
        $company = $this->getCompany($request);

        if (! $communication->configured()) {
            return redirect()->route('settings.whatsapp')
                ->withErrors(['whatsapp' => 'API de comunicacao nao configurada. Verifique COMMUNICATION_API_URL e COMMUNICATION_API_TOKEN.']);
        }

        try {
            $currentSession = $this->getWhatsappSessionSnapshot($company->id);
            $uuid = (string) ($currentSession['uuid'] ?? '');

            $session = $uuid !== ''
                ? $communication->getWhatsappSessionQr($uuid)
                : $communication->createWhatsappSession([
                    'system_slug' => 'aqatende',
                    'external_tenant_id' => (string) $company->id,
                    'external_unit_id' => null,
                    'name' => 'WhatsApp '.$company->id,
                    'callback_base_url' => config('app.url'),
                ]);

            $this->storeWhatsappSessionSnapshot($company->id, $session);

            return redirect()->route('settings.whatsapp')->with('status', 'QR Code atualizado.');
        } catch (Throwable $exception) {
            return redirect()->route('settings.whatsapp')
                ->withErrors(['whatsapp' => 'Nao foi possivel gerar o QR Code: '.$exception->getMessage()]);
        }
    }

    public function refreshWhatsappStatus(Request $request, CommunicationClient $communication): RedirectResponse
    {
        $company = $this->getCompany($request);
        $session = $this->getWhatsappSessionSnapshot($company->id);
        $uuid = (string) ($session['uuid'] ?? '');

        if (! $communication->configured()) {
            return redirect()->route('settings.whatsapp')
                ->withErrors(['whatsapp' => 'API de comunicacao nao configurada. Verifique COMMUNICATION_API_URL e COMMUNICATION_API_TOKEN.']);
        }

        if ($uuid === '') {
            return redirect()->route('settings.whatsapp')
                ->withErrors(['whatsapp' => 'Gere o QR Code antes de atualizar o status.']);
        }

        try {
            $session = $communication->getWhatsappSessionStatus($uuid);
            $this->storeWhatsappSessionSnapshot($company->id, $session);

            return redirect()->route('settings.whatsapp')->with('status', 'Status atualizado.');
        } catch (Throwable $exception) {
            return redirect()->route('settings.whatsapp')
                ->withErrors(['whatsapp' => 'Nao foi possivel atualizar o status: '.$exception->getMessage()]);
        }
    }

    public function generateWhatsappPairingCode(Request $request, CommunicationClient $communication): RedirectResponse
    {
        $company = $this->getCompany($request);

        $data = $request->validate([
            'phone' => ['required', 'string', 'max:20'],
        ]);

        if (! $communication->configured()) {
            return redirect()->route('settings.whatsapp')
                ->withErrors(['whatsapp' => 'API de comunicacao nao configurada. Verifique COMMUNICATION_API_URL e COMMUNICATION_API_TOKEN.']);
        }

        try {
            $session = $this->getWhatsappSessionSnapshot($company->id);
            $uuid = (string) ($session['uuid'] ?? '');

            if ($uuid === '') {
                $session = $communication->createWhatsappSession([
                    'system_slug' => 'aqatende',
                    'external_tenant_id' => (string) $company->id,
                    'external_unit_id' => null,
                    'name' => 'WhatsApp '.$company->id,
                    'callback_base_url' => config('app.url'),
                ]);
                $uuid = (string) ($session['uuid'] ?? '');
            }

            if ($uuid === '') {
                return redirect()->route('settings.whatsapp')
                    ->withErrors(['whatsapp' => 'Nao foi possivel identificar a sessao WhatsApp criada.']);
            }

            $session = $communication->getWhatsappPairingCode($uuid, $data['phone']);
            $this->storeWhatsappSessionSnapshot($company->id, $session);

            return redirect()->route('settings.whatsapp')->with('status', 'Codigo de pareamento gerado.');
        } catch (Throwable $exception) {
            return redirect()->route('settings.whatsapp')
                ->withErrors(['whatsapp' => 'Nao foi possivel gerar o codigo: '.$exception->getMessage()]);
        }
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
}
