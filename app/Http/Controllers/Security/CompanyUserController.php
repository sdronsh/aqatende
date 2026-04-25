<?php

namespace App\Http\Controllers\Security;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use App\Services\Licenses\LicenseEnforcer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use App\Models\Company;

class CompanyUserController extends Controller
{
    public function companyMatrix(Request $request): View
    {
        $actor = $request->user();
        $companyId = $request->session()->get('active_company_id');
        if (! $companyId && ! $actor->is_platform_admin) {
            abort(403);
        }

        $query = Company::query()->orderBy('name');
        if (! $actor->is_platform_admin) {
            $query->whereKey($companyId);
        }

        $query->with([
            'users' => function ($userQuery): void {
                $userQuery
                    ->where('users.is_platform_admin', false)
                    ->orderBy('users.name');
            },
            'roles' => function ($roleQuery): void {
                $roleQuery->orderBy('name');
            },
        ]);

        $perPage = $request->string('per_page', '10')->toString();
        if (! in_array($perPage, ['10', '20', '50', 'all'], true)) {
            $perPage = '10';
        }

        $companies = $perPage === 'all'
            ? $query->get()
            : $query->paginate((int) $perPage)->withQueryString();

        return view('security.users.company-matrix', [
            'companies' => $companies,
            'perPage' => $perPage,
        ]);
    }

    public function index(Request $request): View
    {
        $companyId = $request->session()->get('active_company_id');
        if (! $companyId) {
            abort(403);
        }

        $query = User::query()
            ->select('users.*', 'company_user.role_id', 'company_user.is_master')
            ->join('company_user', 'company_user.user_id', '=', 'users.id')
            ->where('company_user.company_id', $companyId)
            ->where('users.is_platform_admin', false)
            ->orderBy('users.name');

        $perPage = $request->string('per_page', '10')->toString();
        if (! in_array($perPage, ['10', '20', '50', 'all'], true)) {
            $perPage = '10';
        }

        $users = $perPage === 'all'
            ? $query->get()
            : $query->paginate((int) $perPage)->withQueryString();

        $roles = Role::where('company_id', $companyId)->orderBy('name')->get()->keyBy('id');

        return view('security.users.index', [
            'users' => $users,
            'roles' => $roles,
            'perPage' => $perPage,
        ]);
    }

    public function create(Request $request): View
    {
        $user = $request->user();
        $companyId = $request->session()->get('active_company_id');
        if (! $companyId && ! $user->is_platform_admin) {
            abort(403);
        }

        $companies = $user->is_platform_admin
            ? \App\Models\Company::orderBy('name')->get()
            : \App\Models\Company::whereKey($companyId)->get();

        $selectedCompanyId = (int) old('company_id', $companyId ?? $companies->first()?->id);

        $rolesQuery = Role::query();
        if (! $user->is_platform_admin && $companyId) {
            $rolesQuery->where('company_id', $companyId);
        }

        return view('security.users.create', [
            'roles' => $rolesQuery->orderBy('name')->get(),
            'companies' => $companies,
            'selectedCompanyId' => $selectedCompanyId,
            'companyLocked' => ! $user->is_platform_admin,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $actor = $request->user();
        $data = $request->validate([
            'company_id' => [$actor->is_platform_admin ? 'required' : 'nullable', 'integer', 'exists:companies,id'],
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:50'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:6'],
            'role_id' => ['nullable', 'integer', 'exists:roles,id'],
            'is_master' => ['nullable', 'boolean'],
        ]);

        $companyId = $actor->is_platform_admin
            ? (int) $data['company_id']
            : (int) $request->session()->get('active_company_id');
        if (! $companyId) {
            abort(403);
        }

        $limitError = app(LicenseEnforcer::class)->canCreateUser($companyId);
        if ($limitError) {
            return back()->withErrors([
                'username' => $limitError,
            ])->withInput();
        }

        if (! empty($data['role_id']) && ! Role::where('company_id', $companyId)->whereKey($data['role_id'])->exists()) {
            return back()->withErrors([
                'role_id' => 'Perfil invalido para esta empresa.',
            ])->withInput();
        }

        $existingUser = User::where('email', $data['email'])->first();
        if ($existingUser && $existingUser->is_platform_admin) {
            return back()->withErrors([
                'email' => 'Usuario master nao pode ser vinculado a empresa.',
            ])->withInput();
        }

        $usernameOwner = User::where('username', $data['username'])
            ->whereHas('companies', fn ($query) => $query->whereKey($companyId))
            ->first();
        if ($usernameOwner && (! $existingUser || $usernameOwner->id !== $existingUser->id)) {
            return back()->withErrors([
                'username' => 'Username já utilizado nesta empresa.',
            ])->withInput();
        }

        if ($existingUser && $existingUser->companies()->where('companies.id', '!=', $companyId)->exists()) {
            return back()->withErrors([
                'email' => 'Este usuário já está vinculado a outra empresa.',
            ])->withInput();
        }
        if ($existingUser && $existingUser->username !== $data['username']) {
            return back()->withErrors([
                'email' => 'Email já utilizado por outro usuario.',
            ])->withInput();
        }

        $user = User::updateOrCreate(
            ['email' => $data['email']],
            [
                'name' => $data['name'],
                'username' => $data['username'],
                'password' => Hash::make($data['password']),
            ]
        );

        $user->companies()->syncWithoutDetaching([
            $companyId => [
                'role_id' => $data['role_id'] ?? null,
                'is_master' => $request->user()->is_platform_admin ? (bool) ($data['is_master'] ?? false) : false,
            ],
        ]);

        return redirect()->route('security.users.index')->with('status', 'Usuário criado.');
    }

    public function edit(Request $request, User $user): View
    {
        $companyId = $request->session()->get('active_company_id');
        if (! $companyId) {
            abort(403);
        }

        if ($user->is_platform_admin) {
            abort(403);
        }

        if (! $user->companies()->whereKey($companyId)->exists()) {
            abort(403);
        }

        $pivot = $user->companies()->whereKey($companyId)->first()?->pivot;
        $companies = \App\Models\Company::whereKey($companyId)->get();

        return view('security.users.edit', [
            'user' => $user,
            'roles' => Role::where('company_id', $companyId)->orderBy('name')->get(),
            'pivot' => $pivot,
            'companies' => $companies,
            'selectedCompanyId' => $companyId,
            'companyLocked' => true,
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $companyId = $request->session()->get('active_company_id');
        if (! $companyId) {
            abort(403);
        }

        if ($user->is_platform_admin) {
            abort(403);
        }

        if (! $user->companies()->whereKey($companyId)->exists()) {
            abort(403);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:50'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['nullable', 'string', 'min:6'],
            'role_id' => ['nullable', 'integer', 'exists:roles,id'],
            'is_master' => ['nullable', 'boolean'],
        ]);

        if (! empty($data['role_id']) && ! Role::where('company_id', $companyId)->whereKey($data['role_id'])->exists()) {
            return back()->withErrors([
                'role_id' => 'Perfil invalido para esta empresa.',
            ])->withInput();
        }

        $usernameOwner = User::where('username', $data['username'])
            ->whereHas('companies', fn ($query) => $query->whereKey($companyId))
            ->whereKeyNot($user->id)
            ->first();
        if ($usernameOwner) {
            return back()->withErrors([
                'username' => 'Username já utilizado nesta empresa.',
            ])->withInput();
        }

        $user->name = $data['name'];
        $user->username = $data['username'];
        $user->email = $data['email'];
        if (! empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }
        $user->save();

        $user->companies()->updateExistingPivot($companyId, [
            'role_id' => $data['role_id'] ?? null,
            'is_master' => $request->user()->is_platform_admin ? (bool) ($data['is_master'] ?? false) : false,
        ]);

        return redirect()->route('security.users.index')->with('status', 'Usuário atualizado.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        $companyId = $request->session()->get('active_company_id');
        if (! $companyId) {
            abort(403);
        }

        $user->companies()->detach($companyId);

        return redirect()->route('security.users.index')->with('status', 'Usuário removido.');
    }
}
