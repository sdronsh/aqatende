<?php

namespace App\Http\Controllers\Security;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RoleController extends Controller
{
    public function index(Request $request): View
    {
        $companyId = $request->session()->get('active_company_id');
        if (! $companyId) {
            abort(403);
        }

        $query = Role::where('company_id', $companyId)->orderBy('name');

        $perPage = $request->string('per_page', '10')->toString();
        if (! in_array($perPage, ['10', '20', '50', 'all'], true)) {
            $perPage = '10';
        }

        $roles = $perPage === 'all'
            ? $query->get()
            : $query->paginate((int) $perPage)->withQueryString();

        return view('security.roles.index', [
            'roles' => $roles,
            'perPage' => $perPage,
        ]);
    }

    public function create(Request $request): View
    {
        $companyId = $request->session()->get('active_company_id');
        if (! $companyId) {
            abort(403);
        }

        return view('security.roles.create', [
            'permissions' => Permission::orderBy('module')->orderBy('resource')->orderBy('action')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $companyId = $request->session()->get('active_company_id');
        if (! $companyId) {
            abort(403);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('roles', 'name')->where('company_id', $companyId)],
            'description' => ['nullable', 'string', 'max:255'],
            'is_default' => ['nullable', 'boolean'],
            'permissions' => ['array'],
            'permissions.*' => ['integer', 'exists:permissions,id'],
        ]);

        $role = Role::create([
            'company_id' => $companyId,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'is_default' => (bool) ($data['is_default'] ?? false),
        ]);

        $role->permissions()->sync($data['permissions'] ?? []);

        return redirect()->route('security.roles.index')->with('status', 'Perfil criado.');
    }

    public function edit(Request $request, Role $role): View
    {
        $companyId = $request->session()->get('active_company_id');
        if (! $companyId || $role->company_id !== $companyId) {
            abort(403);
        }

        return view('security.roles.edit', [
            'role' => $role,
            'permissions' => Permission::orderBy('module')->orderBy('resource')->orderBy('action')->get(),
            'selected' => $role->permissions()->pluck('permissions.id')->all(),
        ]);
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        $companyId = $request->session()->get('active_company_id');
        if (! $companyId || $role->company_id !== $companyId) {
            abort(403);
        }

        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('roles', 'name')->where('company_id', $companyId)->ignore($role->id),
            ],
            'description' => ['nullable', 'string', 'max:255'],
            'is_default' => ['nullable', 'boolean'],
            'permissions' => ['array'],
            'permissions.*' => ['integer', 'exists:permissions,id'],
        ]);

        $role->update([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'is_default' => (bool) ($data['is_default'] ?? false),
        ]);

        $role->permissions()->sync($data['permissions'] ?? []);

        return redirect()->route('security.roles.index')->with('status', 'Perfil atualizado.');
    }

    public function destroy(Request $request, Role $role): RedirectResponse
    {
        $companyId = $request->session()->get('active_company_id');
        if (! $companyId || $role->company_id !== $companyId) {
            abort(403);
        }

        $hasUsers = $role->company->users()->wherePivot('role_id', $role->id)->exists();
        if ($hasUsers) {
            return back()->withErrors([
                'role' => 'Este perfil está vinculado a usuários.',
            ]);
        }

        $role->delete();

        return redirect()->route('security.roles.index')->with('status', 'Perfil removido.');
    }
}
