<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use App\Services\Licenses\LicenseEnforcer;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:50'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'company_code' => ['required', 'string', 'max:20'],
        ]);

        $companyCode = $this->normalizeCnpj((string) $request->input('company_code', ''));
        $company = Company::where('cnpj', $companyCode)->first();
        if (! $company) {
            return back()->withErrors([
                'company_code' => 'CNPJ ou CPF inválido.',
            ])->withInput();
        }

        $limitError = app(LicenseEnforcer::class)->canCreateUser($company->id);
        if ($limitError) {
            return back()->withErrors([
                'username' => $limitError,
            ])->withInput();
        }

        $usernameTaken = User::where('username', $request->username)
            ->whereHas('companies', fn ($query) => $query->whereKey($company->id))
            ->exists();
        if ($usernameTaken) {
            return back()->withErrors([
                'username' => 'Username já utilizado nesta empresa.',
            ])->withInput();
        }

        $defaultRoleId = Role::where('company_id', $company->id)
            ->where('is_default', true)
            ->orderBy('id')
            ->value('id');

        $user = User::create([
            'name' => $request->name,
            'username' => $request->username,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $user->companies()->syncWithoutDetaching([
            $company->id => [
                'role_id' => $defaultRoleId,
                'is_master' => false,
            ],
        ]);

        event(new Registered($user));

        Auth::login($user);

        $request->session()->put('active_company_id', $company->id);

        return redirect(route('dashboard', absolute: false));
    }

    private function normalizeCnpj(string $value): string
    {
        return preg_replace('/\D/', '', $value) ?? '';
    }
}
