<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\Term;
use App\Services\Licenses\LicenseClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

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

    public function logo(Request $request): View
    {
        $company = $this->getCompany($request);

        return view('settings/logo', [
            'company' => $company,
            'logoPath' => $this->getSetting($company->id, 'logo_path'),
        ]);
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
}
