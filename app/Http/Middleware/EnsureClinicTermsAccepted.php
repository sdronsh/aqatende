<?php

namespace App\Http\Middleware;

use App\Models\Clinic;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureClinicTermsAccepted
{
    public function handle(Request $request, Closure $next): Response
    {
        $companyId = $request->session()->get('active_company_id');
        if (! $companyId) {
            return $next($request);
        }

        $clinic = Clinic::where('company_id', $companyId)->first();
        if (! $clinic || $clinic->hasAcceptedTerms()) {
            return $next($request);
        }

        if ($request->isMethod('get')) {
            $request->session()->put('url.intended', $request->fullUrl());
        }

        return $this->redirectToTerms($clinic);
    }

    private function redirectToTerms(Clinic $clinic): RedirectResponse
    {
        return redirect()
            ->route('clinics.edit', ['clinic' => $clinic, 'tab' => 'terms'])
            ->with('warning', 'Assinatura pendente: aceite o Termo de Uso para liberar agendamento e atendimento.');
    }
}
