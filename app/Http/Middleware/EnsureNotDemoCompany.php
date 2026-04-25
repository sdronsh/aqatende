<?php

namespace App\Http\Middleware;

use App\Models\Company;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureNotDemoCompany
{
    public function handle(Request $request, Closure $next): Response
    {
        if (in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true)) {
            return $next($request);
        }

        if ($request->routeIs('logout')) {
            return $next($request);
        }

        $companyId = $request->session()->get('active_company_id');
        if (! $companyId) {
            return $next($request);
        }

        $isDemo = Company::whereKey($companyId)->value('is_demo');
        if (! $isDemo) {
            return $next($request);
        }

        $message = 'Empresa em modo demonstracao: alteracoes bloqueadas.';
        if ($request->expectsJson()) {
            return response()->json(['message' => $message], 403);
        }

        return back()->with('warning', $message);
    }
}
