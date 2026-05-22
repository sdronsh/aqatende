<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Clinic;
use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\Permission;
use App\Models\Role;
use App\Services\Licenses\LicenseEnforcer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class CompanyController extends Controller
{
    public function index(Request $request): View
    {
        $query = Company::query()->orderBy('name');

        if ($request->filled('search')) {
            $search = $request->string('search')->trim()->toString();
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('cnpj', 'like', "%{$search}%")
                    ->orWhere('license_code', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $perPage = $request->string('per_page', '10')->toString();
        if (! in_array($perPage, ['10', '20', '50', 'all'], true)) {
            $perPage = '10';
        }

        $companies = $perPage === 'all'
            ? $query->get()
            : $query->paginate((int) $perPage)->withQueryString();

        return view('companies.index', [
            'companies' => $companies,
            'perPage' => $perPage,
        ]);
    }

    public function create(): View
    {
        return view('companies.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'legal_name' => ['nullable', 'string', 'max:255'],
            'cnpj' => ['required', 'string', 'max:20', 'unique:companies,cnpj'],
            'license_code' => ['nullable', 'string', 'max:100'],
            'business_activity' => ['required', 'string', 'in:'.implode(',', array_keys(Company::businessActivityOptions()))],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'active' => ['nullable', 'boolean'],
        ]);

        $data['cnpj'] = preg_replace('/\D/', '', $data['cnpj']) ?? null;
        $data['active'] = $request->has('active');

        $company = Company::create($data);
        $this->applyDefaultLogo($company);

        Clinic::create([
            'company_id' => $company->id,
            'code' => $company->code,
            'name' => $company->name,
            'legal_name' => $company->legal_name,
            'cnpj' => $company->cnpj,
            'email' => $company->email,
            'phone' => $company->phone,
            'active' => $company->active,
        ]);

        $adminRole = Role::create([
            'company_id' => $company->id,
            'name' => 'Admin',
            'description' => 'Acesso total',
            'is_default' => true,
        ]);
        $adminRole->permissions()->sync(Permission::pluck('id')->all());

        return redirect()->route('admin.companies.index')->with('status', 'Empresa criada.');
    }

    public function edit(Company $company): View
    {
        return view('companies.edit', [
            'company' => $company,
            'users' => $company->users()->where('users.is_platform_admin', false)->orderBy('name')->get(),
            'roles' => $company->roles()->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Company $company): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'legal_name' => ['nullable', 'string', 'max:255'],
            'cnpj' => ['required', 'string', 'max:20', 'unique:companies,cnpj,'.$company->id],
            'license_code' => ['nullable', 'string', 'max:100'],
            'business_activity' => ['required', 'string', 'in:'.implode(',', array_keys(Company::businessActivityOptions()))],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'active' => ['nullable', 'boolean'],
        ]);

        $data['cnpj'] = preg_replace('/\D/', '', $data['cnpj']) ?? null;
        $data['active'] = $request->has('active');

        $company->update($data);

        $clinic = Clinic::where('company_id', $company->id)->orderBy('id')->first();
        $mirror = [
            'company_id' => $company->id,
            'code' => $company->code,
            'name' => $company->name,
            'legal_name' => $company->legal_name,
            'cnpj' => $company->cnpj,
            'email' => $company->email,
            'phone' => $company->phone,
            'active' => $company->active,
        ];
        if ($clinic) {
            $clinic->update($mirror);
        } else {
            Clinic::create($mirror);
        }

        return redirect()->route('admin.companies.index')->with('status', 'Empresa atualizada.');
    }

    public function storeUser(Request $request, Company $company): RedirectResponse
    {
        $limitError = app(LicenseEnforcer::class)->canCreateUser($company->id);
        if ($limitError) {
            return back()->withErrors([
                'username' => $limitError,
            ])->withInput();
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:50'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:6'],
            'role_id' => ['nullable', 'integer', 'exists:roles,id'],
        ]);

        if (! empty($data['role_id']) && ! $company->roles()->whereKey($data['role_id'])->exists()) {
            return back()->withErrors([
                'role_id' => 'Perfil invalido para esta empresa.',
            ])->withInput();
        }

        $roleId = $data['role_id'] ?? $company->roles()->where('is_default', true)->orderBy('id')->value('id');

        $existingUser = \App\Models\User::where('email', $data['email'])->first();
        if ($existingUser && $existingUser->is_platform_admin) {
            return back()->withErrors([
                'email' => 'Usuario master nao pode ser vinculado a empresa.',
            ])->withInput();
        }
        $usernameOwner = \App\Models\User::where('username', $data['username'])
            ->whereHas('companies', fn ($query) => $query->whereKey($company->id))
            ->first();
        if ($usernameOwner && (! $existingUser || $usernameOwner->id !== $existingUser->id)) {
            return back()->withErrors([
                'username' => 'Username já utilizado nesta empresa.',
            ])->withInput();
        }
        if ($existingUser && $existingUser->companies()->where('companies.id', '!=', $company->id)->exists()) {
            return back()->withErrors([
                'email' => 'Este usuário já está vinculado a outra empresa.',
            ])->withInput();
        }
        if ($existingUser && $existingUser->username !== $data['username']) {
            return back()->withErrors([
                'email' => 'Email já utilizado por outro usuario.',
            ])->withInput();
        }

        $user = \App\Models\User::updateOrCreate(
            ['email' => $data['email']],
            [
                'name' => $data['name'],
                'username' => $data['username'],
                'password' => \Illuminate\Support\Facades\Hash::make($data['password']),
            ]
        );

        $company->users()->syncWithoutDetaching([
            $user->id => [
                'role_id' => $roleId,
                'is_master' => false,
            ],
        ]);

        return redirect()->route('admin.companies.edit', $company)->with('status', 'Usuário vinculado.');
    }

    public function destroyUser(Company $company, \App\Models\User $user): RedirectResponse
    {
        $company->users()->detach($user->id);

        return redirect()->route('admin.companies.edit', $company)->with('status', 'Usuário removido.');
    }

    public function destroy(Company $company): RedirectResponse
    {
        if ($company->users()->exists()) {
            return back()->withErrors([
                'company' => 'Esta empresa possui usuários vinculados.',
            ]);
        }

        $company->delete();

        return redirect()->route('admin.companies.index')->with('status', 'Empresa removida.');
    }

    private function applyDefaultLogo(Company $company): void
    {
        $logoPath = 'company_logos/aqatende-default.png';
        $sourcePath = public_path('logo.png');

        if (is_file($sourcePath) && ! Storage::disk('public')->exists($logoPath)) {
            Storage::disk('public')->put($logoPath, file_get_contents($sourcePath));
        }

        if (Storage::disk('public')->exists($logoPath)) {
            CompanySetting::updateOrCreate(
                ['company_id' => $company->id, 'key' => 'logo_path'],
                ['value' => $logoPath]
            );
        }
    }
}
