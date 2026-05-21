<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Clinic;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;

class SubscriptionController extends Controller
{
    private const PENDING_SESSION_KEY = 'subscription.pending';

    public function create(string $plan): View
    {
        $planData = $this->resolvePlan($plan);

        return view('subscriptions.create', [
            'plan' => $planData,
        ]);
    }

    public function store(Request $request, string $plan): RedirectResponse
    {
        $planData = $this->resolvePlan($plan);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'cnpj' => [
                'required',
                'string',
                'max:30',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $digits = preg_replace('/\D/', '', (string) $value) ?? '';
                    if (! in_array(strlen($digits), [11, 14], true)) {
                        $fail('Informe um CPF ou CNPJ válido.');
                    }
                },
            ],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'contact_name' => ['required', 'string', 'max:255'],
            'contact_email' => ['required', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:30'],
            'address_zip' => ['nullable', 'string', 'max:20'],
            'address_street' => ['nullable', 'string', 'max:255'],
            'address_number' => ['nullable', 'string', 'max:30'],
            'address_complement' => ['nullable', 'string', 'max:120'],
            'address_neighborhood' => ['nullable', 'string', 'max:120'],
            'address_city' => ['nullable', 'string', 'max:120'],
            'address_state' => ['nullable', 'string', 'size:2'],
        ]);
        $data['cnpj'] = preg_replace('/\D/', '', (string) $data['cnpj']) ?? '';

        $baseUrl = (string) config('aqamed.license.api_url');
        $endpoint = (string) config('aqamed.license.companies_endpoint', '/api/companies');
        $token = (string) config('aqamed.license.api_token');
        $systemId = config('aqamed.license.system_id');

        if ($baseUrl === '' || $token === '' || empty($systemId)) {
            return back()
                ->withInput()
                ->withErrors(['plan' => 'API de licencas nao configurada. Verifique LICENSES_API_URL, LICENSES_API_TOKEN e APP_ID.']);
        }

        $payload = array_merge($data, [
            'system_id' => (int) $systemId,
            'user_limit' => $planData['professional_limit'],
            'professional_limit' => $planData['professional_limit'],
            'clinic_limit' => $planData['company_limit'],
            'unit_limit' => $planData['unit_limit'],
            'monthly_amount' => $planData['amount'],
            'plan_code' => $planData['slug'],
            'plan_name' => $planData['name'],
            'license_expires_at' => now()->addMonth()->toDateString(),
        ]);

        $response = Http::acceptJson()
            ->withToken($token)
            ->timeout(10)
            ->post(rtrim($baseUrl, '/').$endpoint, $payload);

        if (! $response->successful()) {
            return back()
                ->withInput()
                ->withErrors($this->licenseApiErrors($response, 'plan', 'Nao foi possivel criar a contratacao na API de licencas.'));
        }

        $payload = $response->json();
        $licenseId = $this->extractLicenseId($payload);
        if (! $licenseId) {
            return back()
                ->withInput()
                ->withErrors(['plan' => 'A API criou a empresa, mas nao retornou o numero da licenca.']);
        }

        $request->session()->put(self::PENDING_SESSION_KEY, [
            'plan' => $planData['slug'],
            'license_id' => $licenseId,
            'license_code' => (string) $licenseId,
            'company_data' => $data,
            'company_name' => $data['name'],
            'cnpj' => $data['cnpj'],
        ]);

        return redirect()
            ->route('subscriptions.billing', $planData['slug'])
            ->with('status', 'Licenca criada. Agora informe os dados da assinatura.');
    }

    public function billing(Request $request, string $plan): View|RedirectResponse
    {
        $planData = $this->resolvePlan($plan);
        $pending = $this->pendingSubscription($request, $planData['slug']);
        if (! $pending) {
            return redirect()
                ->route('subscriptions.create', $planData['slug'])
                ->withErrors(['plan' => 'Preencha os dados da empresa antes de configurar a assinatura.']);
        }

        return view('subscriptions.billing', [
            'plan' => $planData,
            'pending' => $pending,
        ]);
    }

    public function storeBilling(Request $request, string $plan): RedirectResponse
    {
        $planData = $this->resolvePlan($plan);
        $pending = $this->pendingSubscription($request, $planData['slug']);
        if (! $pending) {
            return redirect()
                ->route('subscriptions.create', $planData['slug'])
                ->withErrors(['plan' => 'Preencha os dados da empresa antes de configurar a assinatura.']);
        }

        $data = $request->validate([
            'due_day' => ['required', 'integer', 'min:1', 'max:31'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $baseUrl = (string) config('aqamed.license.api_url');
        $endpoint = (string) config('aqamed.license.subscriptions_endpoint', '/api/subscriptions');
        $token = (string) config('aqamed.license.api_token');

        if ($baseUrl === '' || $token === '') {
            return back()
                ->withInput()
                ->withErrors(['subscription' => 'API de licencas nao configurada. Verifique LICENSES_API_URL e LICENSES_API_TOKEN.']);
        }

        $payload = [
            'license_id' => (int) $pending['license_id'],
            'plan_name' => $planData['api_plan_name'],
            'monthly_amount' => $planData['amount'],
            'due_day' => (int) $data['due_day'],
            'generate_current_invoice' => true,
            'reference_month' => now()->format('Y-m'),
            'notes' => $data['notes'] ?? 'Assinatura criada via site.',
        ];

        $response = Http::acceptJson()
            ->withToken($token)
            ->timeout(10)
            ->post(rtrim($baseUrl, '/').$endpoint, $payload);

        if (! $response->successful()) {
            return back()
                ->withInput()
                ->withErrors($this->licenseApiErrors($response, 'subscription', 'Nao foi possivel criar a assinatura na API de licencas.'));
        }

        $paymentUrl = $this->extractPaymentUrl($response->json());
        $pending['subscription_created'] = true;
        $pending['payment_url'] = $paymentUrl;
        $request->session()->put(self::PENDING_SESSION_KEY, $pending);

        return redirect()
            ->route('subscriptions.admin', $planData['slug'])
            ->with('status', 'Assinatura criada. Agora cadastre o usuario administrativo do sistema.');
    }

    public function adminUser(Request $request, string $plan): View|RedirectResponse
    {
        $planData = $this->resolvePlan($plan);
        $pending = $this->pendingSubscription($request, $planData['slug']);
        if (! $pending || empty($pending['subscription_created'])) {
            return redirect()
                ->route('subscriptions.billing', $planData['slug'])
                ->withErrors(['subscription' => 'Configure a assinatura antes de cadastrar o usuario administrativo.']);
        }

        return view('subscriptions.admin-user', [
            'plan' => $planData,
            'pending' => $pending,
        ]);
    }

    public function storeAdminUser(Request $request, string $plan): RedirectResponse
    {
        $planData = $this->resolvePlan($plan);
        $pending = $this->pendingSubscription($request, $planData['slug']);
        if (! $pending || empty($pending['subscription_created'])) {
            return redirect()
                ->route('subscriptions.billing', $planData['slug'])
                ->withErrors(['subscription' => 'Configure a assinatura antes de cadastrar o usuario administrativo.']);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:50'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        $companyData = is_array($pending['company_data'] ?? null) ? $pending['company_data'] : [];
        if (empty($companyData['name']) || empty($companyData['cnpj'])) {
            return redirect()
                ->route('subscriptions.create', $planData['slug'])
                ->withErrors(['plan' => 'Dados da empresa nao encontrados. Reinicie a contratacao.']);
        }

        $existingUser = User::where('email', $data['email'])->first();
        if ($existingUser?->is_platform_admin) {
            return back()
                ->withInput()
                ->withErrors(['email' => 'Usuario master nao pode ser usado como administrador da empresa.']);
        }

        if ($existingUser && $existingUser->companies()->exists()) {
            return back()
                ->withInput()
                ->withErrors(['email' => 'Este e-mail ja esta vinculado a uma empresa.']);
        }

        $company = Company::updateOrCreate(
            ['cnpj' => preg_replace('/\D/', '', (string) $companyData['cnpj'])],
            [
                'name' => $companyData['name'],
                'legal_name' => $companyData['name'],
                'license_code' => $pending['license_code'] ?? (string) $pending['license_id'],
                'email' => $companyData['email'] ?? null,
                'phone' => $companyData['phone'] ?? null,
                'active' => true,
                'is_demo' => false,
            ]
        );

        $role = Role::updateOrCreate(
            ['company_id' => $company->id, 'name' => 'Administrativo'],
            ['description' => 'Acesso administrativo completo', 'is_default' => true]
        );
        $role->permissions()->sync(Permission::pluck('id')->all());

        $clinic = Clinic::updateOrCreate(
            ['company_id' => $company->id],
            [
                'name' => $companyData['name'],
                'legal_name' => $companyData['name'],
                'trade_name' => $companyData['name'],
                'cnpj' => preg_replace('/\D/', '', (string) $companyData['cnpj']),
                'email' => $companyData['email'] ?? null,
                'phone' => $companyData['phone'] ?? null,
                'schedule_start_time' => '08:00:00',
                'schedule_end_time' => '18:00:00',
                'active' => true,
                'terms_version' => null,
                'terms_accepted_at' => null,
                'terms_accepted_ip' => null,
                'terms_accepted_user_id' => null,
            ]
        );

        Unit::updateOrCreate(
            ['clinic_id' => $clinic->id, 'name' => 'Unidade Principal'],
            [
                'address_line1' => trim((string) ($companyData['address_street'] ?? 'Endereco nao informado').' '.(string) ($companyData['address_number'] ?? '')),
                'address_line2' => $companyData['address_complement'] ?? null,
                'city' => ($companyData['address_city'] ?? null) ?: 'Cidade',
                'state' => strtoupper((string) (($companyData['address_state'] ?? null) ?: 'SP')),
                'zip' => ($companyData['address_zip'] ?? null) ?: '00000-000',
                'country' => 'BR',
                'phone' => $companyData['phone'] ?? null,
                'active' => true,
            ]
        );

        $user = User::updateOrCreate(
            ['email' => $data['email']],
            [
                'name' => $data['name'],
                'username' => $data['username'],
                'password' => Hash::make($data['password']),
                'is_platform_admin' => false,
            ]
        );

        $user->companies()->syncWithoutDetaching([
            $company->id => [
                'role_id' => $role->id,
                'is_master' => true,
            ],
        ]);

        $request->session()->forget(self::PENDING_SESSION_KEY);

        return redirect()
            ->route('login', ['mode' => 'company'])
            ->with('status', 'Empresa criada no AQAtende. Acesse com o CNPJ ou CPF e o usuario administrativo cadastrado.');
    }

    private function resolvePlan(string $plan): array
    {
        $plans = [
            'essencial' => [
                'slug' => 'essencial',
                'name' => 'Essencial',
                'api_plan_name' => 'Plano Essencial',
                'amount' => 19.90,
                'professional_limit' => 5,
                'company_limit' => 1,
                'unit_limit' => 1,
            ],
            'anual' => [
                'slug' => 'anual',
                'name' => 'Anual',
                'api_plan_name' => 'Plano Anual',
                'amount' => 199.90,
                'professional_limit' => 10,
                'company_limit' => 1,
                'unit_limit' => 1,
            ],
            'plus' => [
                'slug' => 'plus',
                'name' => 'Plano Plus',
                'api_plan_name' => 'Plano Plus',
                'amount' => 59.90,
                'professional_limit' => null,
                'company_limit' => 1,
                'unit_limit' => 1,
            ],
        ];

        abort_unless(isset($plans[$plan]), 404);

        return $plans[$plan];
    }

    private function pendingSubscription(Request $request, string $plan): ?array
    {
        $pending = $request->session()->get(self::PENDING_SESSION_KEY);
        if (! is_array($pending) || ($pending['plan'] ?? null) !== $plan || empty($pending['license_id'])) {
            return null;
        }

        return $pending;
    }

    private function licenseApiErrors(mixed $response, string $key, string $fallback): array
    {
        $payload = $response->json();
        $errors = is_array($payload) && is_array($payload['errors'] ?? null) ? $payload['errors'] : [];
        $messages = [];

        foreach ($errors as $field => $fieldMessages) {
            foreach ((array) $fieldMessages as $message) {
                $messages[] = $this->licenseApiMessage((string) $field, (string) $message);
            }
        }

        if ($messages === []) {
            $message = is_array($payload) ? (string) ($payload['message'] ?? '') : '';
            $messages[] = $this->licenseApiMessage($key, $message !== '' ? $message : $fallback);
        }

        return [$key => implode(' ', array_values(array_unique($messages)))];
    }

    private function licenseApiMessage(string $field, string $message): string
    {
        $normalized = strtolower($message);

        if ($this->containsValidationRule($normalized, 'exists')) {
            if ($field === 'plan' || $field === 'subscription') {
                return 'A API de licencas recusou dados de configuracao da contratacao. Confira se o APP_ID existe no core de licencas.';
            }

            if ($field === 'system_id') {
                return 'O APP_ID configurado no .env nao existe no core de licencas. Confira o cadastro em Sistemas e use o APP_ID exibido la.';
            }

            if ($field === 'module_ids' || str_starts_with($field, 'module_ids.')) {
                return 'Um ou mais modulos do plano nao existem no core de licencas. Confira os modulos cadastrados para esse sistema.';
            }

            if ($field === 'license_id') {
                return 'A licenca informada nao existe no core de licencas. Reinicie a contratacao.';
            }

            return 'O campo '.$this->licenseApiFieldLabel($field).' selecionado e invalido.';
        }

        if ($this->containsValidationRule($normalized, 'required')) {
            return 'O campo '.$this->licenseApiFieldLabel($field).' e obrigatorio.';
        }

        if ($this->containsValidationRule($normalized, 'email')) {
            return 'O campo '.$this->licenseApiFieldLabel($field).' deve ser um e-mail valido.';
        }

        if ($this->containsValidationRule($normalized, 'integer')) {
            return 'O campo '.$this->licenseApiFieldLabel($field).' deve ser um numero inteiro.';
        }

        if ($this->containsValidationRule($normalized, 'array')) {
            return 'O campo '.$this->licenseApiFieldLabel($field).' deve ser uma lista valida.';
        }

        if (str_contains($normalized, 'validation.')) {
            return 'O campo '.$this->licenseApiFieldLabel($field).' possui um erro de validacao.';
        }

        return $message;
    }

    private function containsValidationRule(string $message, string $rule): bool
    {
        return str_contains($message, 'validation.'.$rule)
            || str_contains($message, ' '.$rule.' ')
            || str_contains($message, ' '.$rule.'.')
            || str_ends_with($message, ' '.$rule);
    }

    private function licenseApiFieldLabel(string $field): string
    {
        return match (true) {
            $field === 'system_id' => 'sistema da aplicacao (APP_ID)',
            $field === 'module_ids' || str_starts_with($field, 'module_ids.') => 'modulos do plano',
            $field === 'license_id' => 'licenca',
            $field === 'due_day' => 'dia de vencimento',
            $field === 'monthly_amount' => 'valor do plano',
            $field === 'plan_name' => 'nome do plano',
            $field === 'plan_code' => 'codigo do plano',
            $field === 'name' => 'nome da empresa',
            $field === 'cnpj' => 'CNPJ ou CPF',
            $field === 'email' => 'e-mail da empresa',
            $field === 'contact_name' => 'nome do responsavel',
            $field === 'contact_email' => 'e-mail do responsavel',
            default => str_replace('_', ' ', $field),
        };
    }

    private function extractLicenseId(mixed $payload): ?int
    {
        if (! is_array($payload)) {
            return null;
        }

        $data = is_array($payload['data'] ?? null) ? $payload['data'] : [];
        $license = is_array($payload['license'] ?? null) ? $payload['license'] : [];
        $company = is_array($payload['company'] ?? null) ? $payload['company'] : [];
        $candidates = [
            $payload['license_id'] ?? null,
            $payload['id'] ?? null,
            $data['license_id'] ?? null,
            $data['id'] ?? null,
            $license['id'] ?? null,
            $license['license_id'] ?? null,
            $company['license_id'] ?? null,
        ];

        foreach ($candidates as $candidate) {
            if (is_numeric($candidate) && (int) $candidate > 0) {
                return (int) $candidate;
            }
        }

        return null;
    }

    private function extractPaymentUrl(mixed $payload): ?string
    {
        if (! is_array($payload)) {
            return null;
        }

        $billing = is_array($payload['billing'] ?? null) ? $payload['billing'] : [];
        $license = is_array($payload['license'] ?? null) ? $payload['license'] : [];
        $data = is_array($payload['data'] ?? null) ? $payload['data'] : [];
        $dataBilling = is_array($data['billing'] ?? null) ? $data['billing'] : [];

        $candidates = [
            $billing['oldest_unpaid_payment_url'] ?? null,
            $billing['payment_url'] ?? null,
            $billing['payment_link'] ?? null,
            $billing['checkout_url'] ?? null,
            $license['payment_url'] ?? null,
            $dataBilling['oldest_unpaid_payment_url'] ?? null,
            $dataBilling['payment_url'] ?? null,
            $data['payment_url'] ?? null,
            $payload['payment_url'] ?? null,
        ];

        foreach ($candidates as $candidate) {
            if (is_string($candidate) && filter_var($candidate, FILTER_VALIDATE_URL)) {
                return $candidate;
            }
        }

        return null;
    }
}
