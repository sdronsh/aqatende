<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\Company;
use App\Services\Communication\WhatsappConnectionChecker;
use App\Services\Licenses\LicenseEnforcer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function __construct(private readonly WhatsappConnectionChecker $whatsappConnectionChecker)
    {
    }

    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $user = $request->user();
        $mode = $request->input('mode', 'company');

        if ($mode === 'patient') {
            $request->session()->forget('active_company_id');

            $conflict = $this->handleSessionConflict($request, $user, $mode);
            if ($conflict) {
                return $conflict;
            }

            $user->active_session_id = $request->session()->getId();
            $user->save();

            return redirect()->intended(route('dashboard', absolute: false));
        }

        if ($mode === 'master') {
            if (! $user->is_platform_admin) {
                Auth::guard('web')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return back()->withErrors([
                    'email' => 'Acesso permitido apenas para usuário master.',
                ]);
            }

            $request->session()->forget('active_company_id');

            $conflict = $this->handleSessionConflict($request, $user, $mode);
            if ($conflict) {
                return $conflict;
            }

            $user->active_session_id = $request->session()->getId();
            $user->save();

            $request->session()->forget('url.intended');

            return redirect()->route('admin.company-select');
        }

        $companyCode = $this->normalizeCnpj((string) $request->input('company_code', ''));

        if ($companyCode === '') {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()->withErrors([
                'company_code' => 'Informe o CNPJ ou CPF para acessar como equipe.',
            ]);
        }

        $company = Company::where('cnpj', $companyCode)->first();

        if (! $company) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()->withErrors([
                'company_code' => 'CNPJ ou CPF inválido.',
            ]);
        }

        if (! $user->is_platform_admin && ! $user->companies()->whereKey($company->id)->exists()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()->withErrors([
                'company_code' => 'Usuário não vinculado à empresa selecionada.',
            ]);
        }

        $accessError = app(LicenseEnforcer::class)->canAccessSystemRealtime($company->id);
        if ($accessError) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()->withErrors([
                'company_code' => $accessError,
            ]);
        }

        $request->session()->put('active_company_id', $company->id);

        $conflict = $this->handleSessionConflict($request, $user, $mode);
        if ($conflict) {
            return $conflict;
        }

        $user->active_session_id = $request->session()->getId();
        $user->save();

        $redirect = redirect()->intended(route('dashboard', absolute: false));
        $whatsappWarning = $this->whatsappConnectionChecker->warningForCompanyLogin($company->id);

        return $whatsappWarning
            ? $redirect->with('warning', $whatsappWarning)
            : $redirect;
    }

    private function normalizeCnpj(string $value): string
    {
        return preg_replace('/\D/', '', $value) ?? '';
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $fromInstalledApp = $request->boolean('pwa_standalone');
        $user = $request->user();
        $sessionId = $request->session()->getId();

        Auth::guard('web')->logout();

        $request->session()->forget('active_company_id');
        $request->session()->invalidate();

        $request->session()->regenerateToken();

        if ($user && $user->active_session_id === $sessionId) {
            $user->active_session_id = null;
            $user->save();
        }

        return $fromInstalledApp
            ? redirect()->route('login', ['mode' => 'company'])
            : redirect('/');
    }

    private function handleSessionConflict(Request $request, $user, string $mode): ?RedirectResponse
    {
        $currentSessionId = $request->session()->getId();
        $previousSessionId = $user->active_session_id;

        if (! $previousSessionId || $previousSessionId === $currentSessionId) {
            return null;
        }

        $request->session()->put('pending_login', [
            'user_id' => $user->id,
            'mode' => $mode,
            'remember' => $request->boolean('remember'),
            'active_company_id' => $request->session()->get('active_company_id'),
            'previous_session_id' => $previousSessionId,
            'intended' => $request->session()->get('url.intended'),
        ]);

        Auth::guard('web')->logout();
        $request->session()->forget('active_company_id');
        $request->session()->regenerate();
        $request->session()->regenerateToken();

        return redirect()->route('login.conflict');
    }
}
