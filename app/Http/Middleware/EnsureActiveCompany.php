<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveCompany
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user) {
            return $next($request);
        }

        if ($user->is_platform_admin) {
            return $next($request);
        }

        $companyId = $request->session()->get('active_company_id');
        if ($companyId && $user->companies()->whereKey($companyId)->exists()) {
            return $next($request);
        }

        $fallbackCompanyId = $user->companies()->orderBy('companies.id')->value('companies.id');
        if ($fallbackCompanyId) {
            $request->session()->put('active_company_id', $fallbackCompanyId);
            return $next($request);
        }

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->withErrors([
            'company_code' => 'Usuario sem empresa vinculada.',
        ]);
    }
}
