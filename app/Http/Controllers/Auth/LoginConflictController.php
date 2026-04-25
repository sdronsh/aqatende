<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class LoginConflictController extends Controller
{
    public function show(Request $request): View|RedirectResponse
    {
        $pending = $request->session()->get('pending_login');
        if (! $pending) {
            return redirect()->route('login');
        }

        return view('auth.login-conflict');
    }

    public function confirm(Request $request): RedirectResponse
    {
        $pending = $request->session()->pull('pending_login');
        if (! $pending) {
            return redirect()->route('login');
        }

        $user = User::find($pending['user_id'] ?? null);
        if (! $user) {
            return redirect()->route('login')->withErrors([
                'username' => 'Sessao expirada. Faça login novamente.',
            ]);
        }

        if (($pending['mode'] ?? 'company') === 'master' && ! $user->is_platform_admin) {
            return redirect()->route('login')->withErrors([
                'username' => 'Acesso permitido apenas para usuário master.',
            ]);
        }

        Auth::loginUsingId($user->id, (bool) ($pending['remember'] ?? false));
        $request->session()->regenerate();

        $previousSessionId = $pending['previous_session_id'] ?? null;
        if ($previousSessionId && config('session.driver') === 'database') {
            DB::table('sessions')->where('id', $previousSessionId)->delete();
        }

        $user->active_session_id = $request->session()->getId();
        $user->save();

        if (! empty($pending['active_company_id'])) {
            $request->session()->put('active_company_id', $pending['active_company_id']);
        }

        $intended = $pending['intended'] ?? route('dashboard');

        return redirect()->to($intended);
    }

    public function cancel(Request $request): RedirectResponse
    {
        $request->session()->forget('pending_login');

        return redirect()->route('login')->with('info', 'Login cancelado.');
    }
}
