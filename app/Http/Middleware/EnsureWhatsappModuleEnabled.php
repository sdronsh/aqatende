<?php

namespace App\Http\Middleware;

use App\Services\Licenses\LicenseEnforcer;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureWhatsappModuleEnabled
{
    public function __construct(private readonly LicenseEnforcer $licenseEnforcer)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->is_platform_admin) {
            return $next($request);
        }

        $companyId = (int) ($request->session()->get('active_company_id') ?? 0);
        if ($companyId <= 0) {
            abort(403);
        }

        if (! $this->licenseEnforcer->hasModule($companyId, 'whatsapp')) {
            abort(403, 'Modulo WhatsApp nao habilitado na licenca.');
        }

        return $next($request);
    }
}
