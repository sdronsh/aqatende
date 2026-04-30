<?php

namespace App\Http\Controllers;

use App\Models\CashFlowEntry;
use App\Models\Clinic;
use App\Models\Professional;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FinanceCashflowController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:financeiro.fluxo_caixa.view')->only(['index']);
    }

    public function index(Request $request): View
    {
        $clinicIds = $this->getClinicIds($request);
        $selectedClinicId = $request->integer('clinic_id') ?: null;

        $query = CashFlowEntry::query()
            ->whereIn('clinic_id', $clinicIds)
            ->with(['clinic', 'unit', 'professional', 'category', 'account', 'user']);

        if ($selectedClinicId && in_array($selectedClinicId, $clinicIds, true)) {
            $query->where('clinic_id', $selectedClinicId);
        }

        if ($request->filled('tipo')) {
            $query->where('tipo', $request->string('tipo')->toString());
        }

        if ($request->filled('professional_id')) {
            $query->where('professional_id', $request->integer('professional_id'));
        }

        if ($request->filled('search')) {
            $search = $request->string('search')->trim()->toString();
            $query->where('descricao', 'like', "%{$search}%");
        }

        if ($request->filled('date_from')) {
            $query->whereDate('data_movimento', '>=', $request->date('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('data_movimento', '<=', $request->date('date_to'));
        }

        $orderBy = $request->string('order_by', 'date_desc')->toString();
        if (! in_array($orderBy, ['date_desc', 'date_asc', 'value_desc', 'value_asc', 'description_asc'], true)) {
            $orderBy = 'date_desc';
        }

        match ($orderBy) {
            'date_asc' => $query->orderBy('data_movimento'),
            'value_desc' => $query->orderByDesc('valor_cents'),
            'value_asc' => $query->orderBy('valor_cents'),
            'description_asc' => $query->orderBy('descricao'),
            default => $query->orderByDesc('data_movimento'),
        };

        $perPage = $request->string('per_page', '10')->toString();
        if (! in_array($perPage, ['10', '20', '50', 'all'], true)) {
            $perPage = '10';
        }

        $entries = $perPage === 'all'
            ? $query->get()
            : $query->paginate((int) $perPage)->withQueryString();

        $clinics = Clinic::whereIn('id', $clinicIds)->orderBy('name')->get();
        $professionals = Professional::query()
            ->whereHas('user.companies', fn ($q) => $q->where('companies.id', $request->session()->get('active_company_id')))
            ->orderBy('display_name')
            ->get();

        return view('finance/cashflow/index', [
            'entries' => $entries,
            'clinics' => $clinics,
            'professionals' => $professionals,
            'perPage' => $perPage,
            'orderBy' => $orderBy,
            'selectedClinicId' => $selectedClinicId,
        ]);
    }

    private function getClinicIds(Request $request): array
    {
        $companyId = $request->session()->get('active_company_id');
        if (! $companyId) {
            abort(403);
        }

        return Clinic::where('company_id', $companyId)->pluck('id')->all();
    }
}
