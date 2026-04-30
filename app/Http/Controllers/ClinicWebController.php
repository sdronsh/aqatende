<?php

namespace App\Http\Controllers;

use App\Models\Clinic;
use App\Models\ClinicBankAccount;
use App\Models\ClinicCertificate;
use App\Models\ClinicContact;
use App\Models\ClinicHealthRegulation;
use App\Models\ClinicInsuranceContract;
use App\Models\ClinicResponsible;
use App\Models\ClinicTaxProfile;
use App\Models\Company;
use App\Models\AccountPayable;
use App\Models\AccountReceivable;
use App\Models\Appointment;
use App\Models\CashFlowEntry;
use App\Models\FinancialAccount;
use App\Models\FinancialCategory;
use App\Models\MedicalRecord;
use App\Models\Payment;
use App\Models\Schedule;
use App\Models\ScheduleBlock;
use App\Models\Specialty;
use App\Models\Service;
use App\Models\Unit;
use App\Models\Term;
use App\Services\Licenses\LicenseEnforcer;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ClinicWebController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Clinic::class);

        $companyId = $request->session()->get('active_company_id');
        if (! $companyId) {
            abort(403);
        }

        $clinicsQuery = Clinic::where('company_id', $companyId)->orderBy('name');

        if ($request->filled('search')) {
            $search = $request->string('search')->trim()->toString();
            $clinicsQuery->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $perPage = $request->string('per_page', '10')->toString();
        if (! in_array($perPage, ['10', '20', '50', 'all'], true)) {
            $perPage = '10';
        }

        $clinics = $perPage === 'all'
            ? $clinicsQuery->get()
            : $clinicsQuery->paginate((int) $perPage)->withQueryString();

        $terms = Term::currentUsage();

        return view('clinics.index', [
            'clinics' => $clinics,
            'perPage' => $perPage,
            'canCreateClinic' => ! Clinic::where('company_id', $companyId)->exists(),
            'termsVersion' => $terms['version'] ?? null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Clinic::class);

        $companyId = $request->session()->get('active_company_id');
        if (! $companyId) {
            abort(403);
        }

        if (Clinic::where('company_id', $companyId)->exists()) {
            return back()->withErrors([
                'name' => 'Apenas uma clínica é permitida por empresa.',
            ])->withInput();
        }

        $company = Company::find($companyId);
        if (! $company) {
            abort(403);
        }

        $limitError = app(LicenseEnforcer::class)->canCreateClinic($companyId);
        if ($limitError) {
            return back()->withErrors([
                'name' => $limitError,
            ])->withInput();
        }

        $this->normalizeCnaeFields($request);
        $this->normalizeDecimalFields($request);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'legal_name' => ['nullable', 'string', 'max:255'],
            'trade_name' => ['nullable', 'string', 'max:255'],
            'cnpj' => ['nullable', 'string', 'max:20'],
            'cnae_main' => ['nullable', 'string', 'max:20'],
            'cnae_secondary' => ['nullable', 'string'],
            'legal_nature' => ['nullable', 'string', 'max:255'],
            'state_registration' => ['nullable', 'string', 'max:30'],
            'municipal_registration' => ['nullable', 'string', 'max:30'],
            'tax_regime' => ['nullable', 'string', 'max:30'],
            'schedule_start_time' => ['nullable', 'date_format:H:i'],
            'schedule_end_time' => ['nullable', 'date_format:H:i'],
            'contact' => ['array'],
            'contact.address_line1' => ['nullable', 'string', 'max:255'],
            'contact.number' => ['nullable', 'string', 'max:20'],
            'contact.complement' => ['nullable', 'string', 'max:255'],
            'contact.district' => ['nullable', 'string', 'max:255'],
            'contact.zip' => ['nullable', 'string', 'max:12'],
            'contact.city' => ['nullable', 'string', 'max:255'],
            'contact.state' => ['nullable', 'string', 'size:2'],
            'contact.phone' => ['nullable', 'string', 'max:30'],
            'contact.whatsapp' => ['nullable', 'string', 'max:30'],
            'contact.email' => ['nullable', 'email', 'max:255'],
            'contact.website' => ['nullable', 'string', 'max:255'],
            'contact.admin_responsible' => ['nullable', 'string', 'max:255'],
            'certificate' => ['array'],
            'certificate.certificate_type' => ['nullable', 'string', 'max:10'],
            'certificate.file_path' => ['nullable', 'string', 'max:255'],
            'certificate.password' => ['nullable', 'string', 'max:255'],
            'certificate.valid_until' => ['nullable', 'date'],
            'certificate.signer_name' => ['nullable', 'string', 'max:255'],
            'tax' => ['array'],
            'tax.tax_regime' => ['nullable', 'string', 'max:30'],
            'tax.option_date' => ['nullable', 'date'],
            'tax.iss_rate' => ['nullable', 'numeric'],
            'tax.service_list_lc116' => ['nullable', 'string', 'max:255'],
            'tax.service_code_municipal' => ['nullable', 'string', 'max:255'],
            'tax.iss_withheld' => ['nullable', 'boolean'],
            'tax.irrf_rate' => ['nullable', 'numeric'],
            'tax.pis_cofins_csll_rate' => ['nullable', 'numeric'],
            'tax.inss_rate' => ['nullable', 'numeric'],
            'tax.nfse_service_code' => ['nullable', 'string', 'max:255'],
            'tax.nfse_operation_nature' => ['nullable', 'string', 'max:255'],
            'tax.iss_taxation_type' => ['nullable', 'string', 'max:255'],
            'tax.special_tax_regime' => ['nullable', 'string', 'max:255'],
            'tax.environment' => ['nullable', 'string', 'max:255'],
            'tax.city_token' => ['nullable', 'string', 'max:255'],
            'tax.nfse_series' => ['nullable', 'string', 'max:255'],
            'tax.nfse_initial_number' => ['nullable', 'integer'],
            'health' => ['array'],
            'health.anvisa' => ['nullable', 'string', 'max:255'],
            'health.cnes' => ['nullable', 'string', 'max:255'],
            'health.sanitary_permit' => ['nullable', 'string', 'max:255'],
            'health.permit_issued_at' => ['nullable', 'date'],
            'health.permit_valid_until' => ['nullable', 'date'],
            'health.tech_responsible_name' => ['nullable', 'string', 'max:255'],
            'health.tech_responsible_council' => ['nullable', 'string', 'max:255'],
            'health.tech_responsible_number' => ['nullable', 'string', 'max:255'],
            'health.specialties' => ['nullable', 'array'],
            'health.specialties.*' => ['integer', Rule::exists('specialties', 'id')->where('company_id', $companyId)],
            'health.ans_enabled' => ['nullable', 'boolean'],
            'health.ans_registration' => ['nullable', 'string', 'max:255'],
            'health.accreditation_type' => ['nullable', 'string', 'max:255'],
            'health.tables_used' => ['nullable', 'string', 'max:255'],
            'health.insurance_plans' => ['nullable', 'string'],
            'bank' => ['array'],
            'bank.bank_name' => ['nullable', 'string', 'max:255'],
            'bank.agency' => ['nullable', 'string', 'max:255'],
            'bank.account' => ['nullable', 'string', 'max:255'],
            'bank.account_type' => ['nullable', 'string', 'max:255'],
            'bank.pix_key' => ['nullable', 'string', 'max:255'],
            'bank.financial_responsible_name' => ['nullable', 'string', 'max:255'],
            'bank.financial_responsible_cpf' => ['nullable', 'string', 'max:20'],
            'bank.billing_email' => ['nullable', 'email', 'max:255'],
            'bank.boleto_config' => ['nullable', 'string'],
            'insurance_contracts' => ['array'],
            'insurance_contracts.*.plan_name' => ['nullable', 'string', 'max:255'],
            'insurance_contracts.*.credential_code' => ['nullable', 'string', 'max:255'],
            'insurance_contracts.*.contract_type' => ['nullable', 'string', 'max:255'],
            'insurance_contracts.*.table_type' => ['nullable', 'string', 'max:255'],
            'insurance_contracts.*.glosa_percent' => ['nullable', 'numeric'],
            'insurance_contracts.*.submission_type' => ['nullable', 'string', 'max:255'],
            'partners' => ['array'],
            'partners.*.name' => ['nullable', 'string', 'max:255'],
            'partners.*.cpf' => ['nullable', 'string', 'max:20'],
            'partners.*.email' => ['nullable', 'email', 'max:255'],
            'partners.*.phone' => ['nullable', 'string', 'max:30'],
            'partners.*.role' => ['nullable', 'string', 'max:255'],
            'partners.*.share_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'partners.*.repasse' => ['nullable', 'boolean'],
            'responsible_technical' => ['array'],
            'responsible_technical.name' => ['nullable', 'string', 'max:255'],
            'responsible_technical.cpf' => ['nullable', 'string', 'max:20'],
            'responsible_technical.council_type' => ['nullable', 'string', 'max:20'],
            'responsible_technical.council_number' => ['nullable', 'string', 'max:30'],
            'responsible_technical.specialty' => ['nullable', 'string', 'max:255'],
            'responsible_technical.certificate_path' => ['nullable', 'string', 'max:255'],
            'responsible_technical.email' => ['nullable', 'email', 'max:255'],
            'responsible_technical.phone' => ['nullable', 'string', 'max:30'],
            'responsible_legal' => ['array'],
            'responsible_legal.name' => ['nullable', 'string', 'max:255'],
            'responsible_legal.cpf' => ['nullable', 'string', 'max:20'],
            'responsible_legal.email' => ['nullable', 'email', 'max:255'],
            'responsible_legal.phone' => ['nullable', 'string', 'max:30'],
            'terms_accept' => $this->termsAcceptRules(),
        ]);

        $data['company_id'] = $company->id;
        $data['code'] = $company->code;
        $data['name'] = $company->name;
        $data['legal_name'] = $company->legal_name;
        $data['cnpj'] = $company->cnpj;
        $data['email'] = $company->email;
        $data['phone'] = $company->phone;
        $data['active'] = $company->active;

        $clinic = DB::transaction(function () use ($request, $data) {
            $contact = $data['contact'] ?? [];
            $certificate = $data['certificate'] ?? [];
            $tax = $data['tax'] ?? [];
            $health = $data['health'] ?? [];
            if (array_key_exists('health', $data) && ! array_key_exists('specialties', $health)) {
                $health['specialties'] = [];
            }
            if (isset($health['specialties']) && is_array($health['specialties'])) {
                $health['specialties'] = json_encode(array_values(array_unique($health['specialties'])));
            }
            $bank = $data['bank'] ?? [];

            $clinicData = collect($data)->except([
                'contact', 'certificate', 'tax', 'health', 'bank',
                'insurance_contracts', 'partners', 'responsible_technical', 'responsible_legal',
                'terms_accept',
            ])->toArray();

            if (! isset($clinicData['email'])) {
                $clinicData['email'] = $contact['email'] ?? null;
            }
            if (! isset($clinicData['phone'])) {
                $clinicData['phone'] = $contact['phone'] ?? null;
            }

            $clinic = Clinic::create(array_merge($clinicData, [
                'company_id' => $request->session()->get('active_company_id'),
            ]));

            ClinicContact::updateOrCreate(['clinic_id' => $clinic->id], $contact);
            ClinicCertificate::updateOrCreate(['clinic_id' => $clinic->id], $certificate);
            ClinicTaxProfile::updateOrCreate(['clinic_id' => $clinic->id], $tax);
            ClinicHealthRegulation::updateOrCreate(['clinic_id' => $clinic->id], $health);
            ClinicBankAccount::updateOrCreate(['clinic_id' => $clinic->id], $bank);

            $clinic->insuranceContracts()->delete();
            $contracts = collect($data['insurance_contracts'] ?? [])
                ->filter(fn ($row) => ! empty($row['plan_name']))
                ->values();
            foreach ($contracts as $row) {
                $clinic->insuranceContracts()->create($row);
            }

            $clinic->partners()->delete();
            $partners = collect($data['partners'] ?? [])
                ->filter(fn ($row) => ! empty($row['name']))
                ->values();
            foreach ($partners as $row) {
                $clinic->partners()->create($row);
            }

            $respTech = $data['responsible_technical'] ?? [];
            ClinicResponsible::updateOrCreate(
                ['clinic_id' => $clinic->id, 'type' => 'technical'],
                $respTech
            );
            $respLegal = $data['responsible_legal'] ?? [];
            ClinicResponsible::updateOrCreate(
                ['clinic_id' => $clinic->id, 'type' => 'legal'],
                $respLegal
            );

            $this->applyTermsAcceptanceIfNeeded($clinic, $request);

            return $clinic;
        });

        if ($request->boolean('terms_accept')) {
            return redirect()->route('clinics.index')->with('status', 'Clínica criada e termo aceito.');
        }

        return redirect()->route('clinics.index')->with('status', 'Clínica criada.');
    }

    public function create(): View
    {
        $this->authorize('create', Clinic::class);

        $companyId = session('active_company_id');
        if (! $companyId) {
            abort(403);
        }

        return view('clinics.create', [
            'specialties' => Specialty::where('company_id', $companyId)
                ->where('active', true)
                ->orderBy('name')
                ->get(),
            'terms' => Term::currentUsage(),
        ]);
    }

    public function edit(Clinic $clinic): View
    {
        $companyId = session('active_company_id');
        if (! $companyId || $clinic->company_id !== $companyId) {
            abort(403);
        }

        $this->authorize('update', $clinic);

        return view('clinics.edit', [
            'clinic' => $clinic->load(
                'contact',
                'certificate',
                'taxProfile',
                'healthRegulation',
                'bankAccount',
                'insuranceContracts',
                'partners',
                'responsibles'
            ),
            'specialties' => Specialty::where('company_id', $companyId)
                ->where('active', true)
                ->orderBy('name')
                ->get(),
            'terms' => Term::currentUsage(),
        ]);
    }

    public function update(Request $request, Clinic $clinic): RedirectResponse
    {
        if ($clinic->company_id !== $request->session()->get('active_company_id')) {
            abort(403);
        }

        $this->authorize('update', $clinic);

        $company = Company::find($clinic->company_id);
        if (! $company) {
            abort(403);
        }

        $this->normalizeCnaeFields($request);
        $this->normalizeDecimalFields($request);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'legal_name' => ['nullable', 'string', 'max:255'],
            'trade_name' => ['nullable', 'string', 'max:255'],
            'cnpj' => ['nullable', 'string', 'max:20'],
            'cnae_main' => ['nullable', 'string', 'max:20'],
            'cnae_secondary' => ['nullable', 'string'],
            'legal_nature' => ['nullable', 'string', 'max:255'],
            'state_registration' => ['nullable', 'string', 'max:30'],
            'municipal_registration' => ['nullable', 'string', 'max:30'],
            'tax_regime' => ['nullable', 'string', 'max:30'],
            'schedule_start_time' => ['nullable', 'date_format:H:i'],
            'schedule_end_time' => ['nullable', 'date_format:H:i'],
            'active' => ['boolean'],
            'contact' => ['array'],
            'contact.address_line1' => ['nullable', 'string', 'max:255'],
            'contact.number' => ['nullable', 'string', 'max:20'],
            'contact.complement' => ['nullable', 'string', 'max:255'],
            'contact.district' => ['nullable', 'string', 'max:255'],
            'contact.zip' => ['nullable', 'string', 'max:12'],
            'contact.city' => ['nullable', 'string', 'max:255'],
            'contact.state' => ['nullable', 'string', 'size:2'],
            'contact.phone' => ['nullable', 'string', 'max:30'],
            'contact.whatsapp' => ['nullable', 'string', 'max:30'],
            'contact.email' => ['nullable', 'email', 'max:255'],
            'contact.website' => ['nullable', 'string', 'max:255'],
            'contact.admin_responsible' => ['nullable', 'string', 'max:255'],
            'certificate' => ['array'],
            'certificate.certificate_type' => ['nullable', 'string', 'max:10'],
            'certificate.file_path' => ['nullable', 'string', 'max:255'],
            'certificate.password' => ['nullable', 'string', 'max:255'],
            'certificate.valid_until' => ['nullable', 'date'],
            'certificate.signer_name' => ['nullable', 'string', 'max:255'],
            'tax' => ['array'],
            'tax.tax_regime' => ['nullable', 'string', 'max:30'],
            'tax.option_date' => ['nullable', 'date'],
            'tax.iss_rate' => ['nullable', 'numeric'],
            'tax.service_list_lc116' => ['nullable', 'string', 'max:255'],
            'tax.service_code_municipal' => ['nullable', 'string', 'max:255'],
            'tax.iss_withheld' => ['nullable', 'boolean'],
            'tax.irrf_rate' => ['nullable', 'numeric'],
            'tax.pis_cofins_csll_rate' => ['nullable', 'numeric'],
            'tax.inss_rate' => ['nullable', 'numeric'],
            'tax.nfse_service_code' => ['nullable', 'string', 'max:255'],
            'tax.nfse_operation_nature' => ['nullable', 'string', 'max:255'],
            'tax.iss_taxation_type' => ['nullable', 'string', 'max:255'],
            'tax.special_tax_regime' => ['nullable', 'string', 'max:255'],
            'tax.environment' => ['nullable', 'string', 'max:255'],
            'tax.city_token' => ['nullable', 'string', 'max:255'],
            'tax.nfse_series' => ['nullable', 'string', 'max:255'],
            'tax.nfse_initial_number' => ['nullable', 'integer'],
            'health' => ['array'],
            'health.anvisa' => ['nullable', 'string', 'max:255'],
            'health.cnes' => ['nullable', 'string', 'max:255'],
            'health.sanitary_permit' => ['nullable', 'string', 'max:255'],
            'health.permit_issued_at' => ['nullable', 'date'],
            'health.permit_valid_until' => ['nullable', 'date'],
            'health.tech_responsible_name' => ['nullable', 'string', 'max:255'],
            'health.tech_responsible_council' => ['nullable', 'string', 'max:255'],
            'health.tech_responsible_number' => ['nullable', 'string', 'max:255'],
            'health.specialties' => ['nullable', 'array'],
            'health.specialties.*' => ['integer', Rule::exists('specialties', 'id')->where('company_id', $clinic->company_id)],
            'health.ans_enabled' => ['nullable', 'boolean'],
            'health.ans_registration' => ['nullable', 'string', 'max:255'],
            'health.accreditation_type' => ['nullable', 'string', 'max:255'],
            'health.tables_used' => ['nullable', 'string', 'max:255'],
            'health.insurance_plans' => ['nullable', 'string'],
            'bank' => ['array'],
            'bank.bank_name' => ['nullable', 'string', 'max:255'],
            'bank.agency' => ['nullable', 'string', 'max:255'],
            'bank.account' => ['nullable', 'string', 'max:255'],
            'bank.account_type' => ['nullable', 'string', 'max:255'],
            'bank.pix_key' => ['nullable', 'string', 'max:255'],
            'bank.financial_responsible_name' => ['nullable', 'string', 'max:255'],
            'bank.financial_responsible_cpf' => ['nullable', 'string', 'max:20'],
            'bank.billing_email' => ['nullable', 'email', 'max:255'],
            'bank.boleto_config' => ['nullable', 'string'],
            'insurance_contracts' => ['array'],
            'insurance_contracts.*.plan_name' => ['nullable', 'string', 'max:255'],
            'insurance_contracts.*.credential_code' => ['nullable', 'string', 'max:255'],
            'insurance_contracts.*.contract_type' => ['nullable', 'string', 'max:255'],
            'insurance_contracts.*.table_type' => ['nullable', 'string', 'max:255'],
            'insurance_contracts.*.glosa_percent' => ['nullable', 'numeric'],
            'insurance_contracts.*.submission_type' => ['nullable', 'string', 'max:255'],
            'partners' => ['array'],
            'partners.*.name' => ['nullable', 'string', 'max:255'],
            'partners.*.cpf' => ['nullable', 'string', 'max:20'],
            'partners.*.email' => ['nullable', 'email', 'max:255'],
            'partners.*.phone' => ['nullable', 'string', 'max:30'],
            'partners.*.role' => ['nullable', 'string', 'max:255'],
            'partners.*.share_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'partners.*.repasse' => ['nullable', 'boolean'],
            'responsible_technical' => ['array'],
            'responsible_technical.name' => ['nullable', 'string', 'max:255'],
            'responsible_technical.cpf' => ['nullable', 'string', 'max:20'],
            'responsible_technical.council_type' => ['nullable', 'string', 'max:20'],
            'responsible_technical.council_number' => ['nullable', 'string', 'max:30'],
            'responsible_technical.specialty' => ['nullable', 'string', 'max:255'],
            'responsible_technical.certificate_path' => ['nullable', 'string', 'max:255'],
            'responsible_technical.email' => ['nullable', 'email', 'max:255'],
            'responsible_technical.phone' => ['nullable', 'string', 'max:30'],
            'responsible_legal' => ['array'],
            'responsible_legal.name' => ['nullable', 'string', 'max:255'],
            'responsible_legal.cpf' => ['nullable', 'string', 'max:20'],
            'responsible_legal.email' => ['nullable', 'email', 'max:255'],
            'responsible_legal.phone' => ['nullable', 'string', 'max:30'],
            'terms_accept' => $this->termsAcceptRules($clinic),
        ]);

        $data['code'] = $company->code;
        $data['name'] = $company->name;
        $data['legal_name'] = $company->legal_name;
        $data['cnpj'] = $company->cnpj;
        $data['email'] = $company->email;
        $data['phone'] = $company->phone;
        $data['active'] = $company->active;

        DB::transaction(function () use ($clinic, $data, $request) {
            $contact = $data['contact'] ?? [];
            $certificate = $data['certificate'] ?? [];
            $tax = $data['tax'] ?? [];
            $health = $data['health'] ?? [];
            if (array_key_exists('health', $data) && ! array_key_exists('specialties', $health)) {
                $health['specialties'] = [];
            }
            if (isset($health['specialties']) && is_array($health['specialties'])) {
                $health['specialties'] = json_encode(array_values(array_unique($health['specialties'])));
            }
            $bank = $data['bank'] ?? [];

            $clinicData = collect($data)->except([
                'contact', 'certificate', 'tax', 'health', 'bank',
                'insurance_contracts', 'partners', 'responsible_technical', 'responsible_legal',
                'terms_accept',
            ])->toArray();

            $clinicData['email'] = $contact['email'] ?? $clinicData['email'] ?? $clinic->email;
            $clinicData['phone'] = $contact['phone'] ?? $clinicData['phone'] ?? $clinic->phone;

            $clinic->update($clinicData);

            ClinicContact::updateOrCreate(['clinic_id' => $clinic->id], $contact);
            ClinicCertificate::updateOrCreate(['clinic_id' => $clinic->id], $certificate);
            ClinicTaxProfile::updateOrCreate(['clinic_id' => $clinic->id], $tax);
            ClinicHealthRegulation::updateOrCreate(['clinic_id' => $clinic->id], $health);
            ClinicBankAccount::updateOrCreate(['clinic_id' => $clinic->id], $bank);

            $clinic->insuranceContracts()->delete();
            $contracts = collect($data['insurance_contracts'] ?? [])
                ->filter(fn ($row) => ! empty($row['plan_name']))
                ->values();
            foreach ($contracts as $row) {
                $clinic->insuranceContracts()->create($row);
            }

            $clinic->partners()->delete();
            $partners = collect($data['partners'] ?? [])
                ->filter(fn ($row) => ! empty($row['name']))
                ->values();
            foreach ($partners as $row) {
                $clinic->partners()->create($row);
            }

            $respTech = $data['responsible_technical'] ?? [];
            ClinicResponsible::updateOrCreate(
                ['clinic_id' => $clinic->id, 'type' => 'technical'],
                $respTech
            );
            $respLegal = $data['responsible_legal'] ?? [];
            ClinicResponsible::updateOrCreate(
                ['clinic_id' => $clinic->id, 'type' => 'legal'],
                $respLegal
            );

            $this->applyTermsAcceptanceIfNeeded($clinic, $request);
        });

        if ($request->boolean('terms_accept')) {
            return redirect()
                ->intended(route('clinics.index'))
                ->with('status', 'Termo de uso aceito.');
        }

        return redirect()->route('clinics.index')->with('status', 'Clínica atualizada.');
    }

    private function normalizeDecimalFields(Request $request): void
    {
        $tax = $request->input('tax', []);
        foreach (['iss_rate', 'irrf_rate', 'pis_cofins_csll_rate', 'inss_rate'] as $field) {
            if (isset($tax[$field]) && is_string($tax[$field])) {
                $tax[$field] = str_replace(',', '.', $tax[$field]);
            }
        }

        $insurance = $request->input('insurance_contracts', []);
        foreach ($insurance as $index => $row) {
            if (isset($row['glosa_percent']) && is_string($row['glosa_percent'])) {
                $row['glosa_percent'] = str_replace(',', '.', $row['glosa_percent']);
            }
            $insurance[$index] = $row;
        }

        $partners = $request->input('partners', []);
        foreach ($partners as $index => $row) {
            if (isset($row['share_percent']) && is_string($row['share_percent'])) {
                $row['share_percent'] = str_replace(',', '.', $row['share_percent']);
            }
            $partners[$index] = $row;
        }

        $request->merge([
            'tax' => $tax,
            'insurance_contracts' => $insurance,
            'partners' => $partners,
        ]);
    }

    private function applyTermsAcceptanceIfNeeded(Clinic $clinic, Request $request): void
    {
        if (! $request->boolean('terms_accept')) {
            return;
        }

        $version = Term::currentUsageVersion();
        if (! $version) {
            return;
        }

        if ($clinic->hasAcceptedTerms($version)) {
            return;
        }

        $clinic->forceFill([
            'terms_version' => $version,
            'terms_accepted_at' => now(),
            'terms_accepted_ip' => $request->ip(),
            'terms_accepted_user_id' => $request->user()?->id,
        ])->save();
    }

    private function termsAcceptRules(?Clinic $clinic = null): array
    {
        $version = Term::currentUsageVersion();
        if ($clinic && $version && $clinic->hasAcceptedTerms($version)) {
            return ['nullable'];
        }

        return ['nullable', 'accepted'];
    }

    private function normalizeCnaeFields(Request $request): void
    {
        $cnaeMain = $request->input('cnae_main');
        if (is_string($cnaeMain)) {
            $normalized = $this->extractCnaeCode($cnaeMain);
            $request->merge(['cnae_main' => $normalized]);
        }
    }

    private function extractCnaeCode(string $value): string
    {
        $normalized = trim($value);
        if ($normalized === '') {
            return $normalized;
        }

        if (str_contains($normalized, ' - ')) {
            $normalized = explode(' - ', $normalized, 2)[0];
        }

        return trim($normalized);
    }

    public function destroy(Clinic $clinic): RedirectResponse
    {
        if ($clinic->company_id !== session('active_company_id')) {
            abort(403);
        }

        $this->authorize('delete', $clinic);

        $hasFinancial = AccountReceivable::where('clinic_id', $clinic->id)->exists()
            || AccountPayable::where('clinic_id', $clinic->id)->exists()
            || CashFlowEntry::where('clinic_id', $clinic->id)->exists();

        if ($hasFinancial) {
            return redirect()
                ->route('clinics.index')
                ->with('error', 'Nao e possivel excluir a clinica com registros financeiros vinculados.');
        }

        DB::transaction(function () use ($clinic) {
            FinancialCategory::where('clinic_id', $clinic->id)->delete();
            FinancialAccount::where('clinic_id', $clinic->id)->delete();

            $unitIds = Unit::where('clinic_id', $clinic->id)->pluck('id');
            $appointmentIds = Appointment::where('clinic_id', $clinic->id)->pluck('id');

            if ($appointmentIds->isNotEmpty()) {
                Payment::whereIn('appointment_id', $appointmentIds)->delete();
                MedicalRecord::whereIn('appointment_id', $appointmentIds)->delete();
                Appointment::whereIn('id', $appointmentIds)->delete();
            }

            if ($unitIds->isNotEmpty()) {
                ScheduleBlock::whereIn('unit_id', $unitIds)->delete();
                Schedule::whereIn('unit_id', $unitIds)->delete();
            }

            Service::where('clinic_id', $clinic->id)->delete();
            Unit::whereIn('id', $unitIds)->delete();

            $clinic->delete();
        });

        return redirect()->route('clinics.index')->with('status', 'Clínica removida.');
    }
}
