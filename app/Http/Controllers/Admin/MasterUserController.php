<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MasterUserController extends Controller
{
    public function index(Request $request): View
    {
        $query = User::where('is_platform_admin', true)->orderBy('name');

        $perPage = $request->string('per_page', '10')->toString();
        if (! in_array($perPage, ['10', '20', '50', 'all'], true)) {
            $perPage = '10';
        }

        $users = $perPage === 'all'
            ? $query->get()
            : $query->paginate((int) $perPage)->withQueryString();

        return view('admin.masters.index', [
            'users' => $users,
            'perPage' => $perPage,
        ]);
    }

    public function create(): View
    {
        return view('admin.masters.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:50', 'unique:users,username'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6'],
        ]);

        User::create([
            'name' => $data['name'],
            'username' => $data['username'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'is_platform_admin' => true,
        ]);

        return redirect()->route('admin.masters.index')->with('status', 'Usuario master criado.');
    }

    public function edit(User $user): View
    {
        if (! $user->is_platform_admin) {
            abort(404);
        }

        return view('admin.masters.edit', [
            'user' => $user,
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        if (! $user->is_platform_admin) {
            abort(404);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:50', Rule::unique('users', 'username')->ignore($user->id)],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:6'],
        ]);

        $user->name = $data['name'];
        $user->username = $data['username'];
        $user->email = $data['email'];
        if (! empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }
        $user->is_platform_admin = true;
        $user->save();

        return redirect()->route('admin.masters.index')->with('status', 'Usuario master atualizado.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if (! $user->is_platform_admin) {
            abort(404);
        }

        if ($request->user()->id === $user->id) {
            return back()->withErrors([
                'user' => 'Voce nao pode excluir seu proprio usuario master.',
            ]);
        }

        $masterCount = User::where('is_platform_admin', true)->count();
        if ($masterCount <= 1) {
            return back()->withErrors([
                'user' => 'E necessario manter pelo menos um usuario master.',
            ]);
        }

        $user->delete();

        return redirect()->route('admin.masters.index')->with('status', 'Usuario master removido.');
    }
}
