<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $companyId = $request->session()->get('active_company_id');
        $user = $request->user();

        if (! $user) {
            abort(403);
        }

        if ($user->is_platform_admin) {
            return $next($request);
        }

        if (! $companyId || ! $user->hasCompanyPermission($companyId, $permission)) {
            abort(403);
        }

        return $next($request);
    }
}
