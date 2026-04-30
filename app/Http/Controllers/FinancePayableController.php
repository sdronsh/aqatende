<?php

namespace App\Http\Controllers;

use App\Models\AccountPayable;
use App\Models\Clinic;
use App\Models\FinancialCategory;
use App\Models\Unit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FinancePayableController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:financeiro.contas_pagar.view')->only(['index']);
        $this->middleware('permission:financeiro.contas_pagar.create')->only(['create', 'store']);
        $this->middleware('permission:financeiro.contas_pagar.update')->only(['edit', 'update']);
    }

    public function index(Request $request): View
    {
        $clinicIds = $this->getClinicIds($request);
        $selectedClinicId = $request->integer('clinic_id') ?: null;

        $query = AccountPayable::query()
            ->whereIn('clinic_id', $clinicIds)
            ->with(['clinic', 'unit', 'category']);

        if ($selectedClinicId && in_array($selectedClinicId, $clinicIds, true)) {
            $query->where('clinic_id', $selectedClinicId);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        if ($request->filled('search')) {
            $search = $request->string('search')->trim()->toString();
            $query->where(function ($q) use ($search) {
                $q->where('descricao', 'like', "%{$search}%")
                    ->orWhere('fornecedor', 'like', "%{$search}%");
            });
        }

        if ($request->filled('date_from')) {
            $query->whereDate('data_vencimento', '>=', $request->date('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('data_vencimento', '<=', $request->date('date_to'));
        }

        $orderBy = $request->string('order_by', 'due_desc')->toString();
        if (! in_array($orderBy, ['due_desc', 'due_asc', 'value_desc', 'value_asc', 'description_asc'], true)) {
            $orderBy = 'due_desc';
        }

        match ($orderBy) {
            'due_asc' => $query->orderBy('data_vencimento'),
            'value_desc' => $query->orderByDesc('valor_cents'),
            'value_asc' => $query->orderBy('valor_cents'),
            'description_asc' => $query->orderBy('descricao'),
            default => $query->orderByDesc('data_vencimento'),
        };

        $perPage = $request->string('per_page', '10')->toString();
        if (! in_array($perPage, ['10', '20', '50', 'all'], true)) {
            $perPage = '10';
        }

        $payables = $perPage === 'all'
            ? $query->get()
            : $query->paginate((int) $perPage)->withQueryString();

        $clinics = Clinic::whereIn('id', $clinicIds)->orderBy('name')->get();

        return view('finance/payables/index', [
            'payables' => $payables,
            'clinics' => $clinics,
            'perPage' => $perPage,
            'orderBy' => $orderBy,
            'selectedClinicId' => $selectedClinicId,
        ]);
    }

    public function create(Request $request): View
    {
        $clinicIds = $this->getClinicIds($request);

        return view('finance/payables/create', [
            'clinics' => Clinic::whereIn('id', $clinicIds)->orderBy('name')->get(),
            'units' => Unit::whereIn('clinic_id', $clinicIds)->orderBy('name')->get(),
            'categories' => FinancialCategory::whereIn('clinic_id', $clinicIds)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $clinicIds = $this->getClinicIds($request);

        $data = $request->validate([
            'clinic_id' => ['required', 'integer', 'exists:clinics,id'],
            'unit_id' => ['nullable', 'integer', 'exists:units,id'],
            'categoria_financeira_id' => ['nullable', 'integer', 'exists:categorias_financeiras,id'],
            'fornecedor' => ['nullable', 'string', 'max:255'],
            'descricao' => ['required', 'string', 'max:255'],
            'valor' => $this->moneyRule(),
            'data_emissao' => ['nullable', 'date'],
            'data_vencimento' => ['required', 'date'],
            'status' => ['nullable', 'string', 'max:20'],
            'forma_pagamento' => ['nullable', 'string', 'max:20'],
            'centro_custo' => ['nullable', 'string', 'max:255'],
            'observacoes' => ['nullable', 'string'],
        ]);

        if (! in_array((int) $data['clinic_id'], $clinicIds, true)) {
            abort(403);
        }

        $data['valor_cents'] = $this->parsePriceToCents($data['valor']);
        $data['status'] = $data['status'] ?? 'aberto';
        unset($data['valor']);

        $payable = AccountPayable::create($data);
        $this->syncCashFlow($payable, false);

        return redirect()->route('finance.payables.index')->with('status', 'Conta a pagar criada.');
    }

    public function edit(Request $request, AccountPayable $payable): View
    {
        $clinicIds = $this->getClinicIds($request);
        if (! in_array($payable->clinic_id, $clinicIds, true)) {
            abort(403);
        }

        return view('finance/payables/edit', [
            'payable' => $payable,
            'clinics' => Clinic::whereIn('id', $clinicIds)->orderBy('name')->get(),
            'units' => Unit::whereIn('clinic_id', $clinicIds)->orderBy('name')->get(),
            'categories' => FinancialCategory::whereIn('clinic_id', $clinicIds)->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, AccountPayable $payable): RedirectResponse
    {
        $clinicIds = $this->getClinicIds($request);
        if (! in_array($payable->clinic_id, $clinicIds, true)) {
            abort(403);
        }

        $data = $request->validate([
            'clinic_id' => ['required', 'integer', 'exists:clinics,id'],
            'unit_id' => ['nullable', 'integer', 'exists:units,id'],
            'categoria_financeira_id' => ['nullable', 'integer', 'exists:categorias_financeiras,id'],
            'fornecedor' => ['nullable', 'string', 'max:255'],
            'descricao' => ['required', 'string', 'max:255'],
            'valor' => $this->moneyRule(),
            'data_emissao' => ['nullable', 'date'],
            'data_vencimento' => ['required', 'date'],
            'status' => ['nullable', 'string', 'max:20'],
            'forma_pagamento' => ['nullable', 'string', 'max:20'],
            'centro_custo' => ['nullable', 'string', 'max:255'],
            'observacoes' => ['nullable', 'string'],
        ]);

        if (! in_array((int) $data['clinic_id'], $clinicIds, true)) {
            abort(403);
        }

        $data['valor_cents'] = $this->parsePriceToCents($data['valor']);
        $data['status'] = $data['status'] ?? 'aberto';
        unset($data['valor']);

        $wasPaid = $payable->status === 'pago';
        $payable->update($data);
        $this->syncCashFlow($payable, $wasPaid);

        return redirect()->route('finance.payables.index')->with('status', 'Conta a pagar atualizada.');
    }

    private function getClinicIds(Request $request): array
    {
        $companyId = $request->session()->get('active_company_id');
        if (! $companyId) {
            abort(403);
        }

        return Clinic::where('company_id', $companyId)->pluck('id')->all();
    }

    private function parsePriceToCents(string $value): int
    {
        $raw = str_replace(['R$', 'r$', ' '], '', trim($value));
        if (str_contains($raw, ',')) {
            $raw = str_replace('.', '', $raw);
            $raw = str_replace(',', '.', $raw);
        }

        $amount = is_numeric($raw) ? (float) $raw : 0.0;

        return (int) round($amount * 100);
    }

    private function moneyRule(bool $required = true): array
    {
        $rule = ['regex:/^\\d{1,3}(\\.\\d{3})*(,\\d{2})?$|^\\d+([.,]\\d{1,2})?$/'];
        array_unshift($rule, $required ? 'required' : 'nullable');

        return $rule;
    }

    private function syncCashFlow(AccountPayable $payable, bool $wasPaid): void
    {
        if ($payable->status !== 'pago') {
            return;
        }

        if (! $payable->pago_em) {
            $payable->pago_em = now();
            $payable->save();
        }

        if ($wasPaid) {
            return;
        }

        $exists = \App\Models\CashFlowEntry::where('origem', 'conta_pagar')
            ->where('origem_id', $payable->id)
            ->exists();

        if ($exists) {
            return;
        }

        \App\Models\CashFlowEntry::create([
            'clinic_id' => $payable->clinic_id,
            'unit_id' => $payable->unit_id,
            'professional_id' => null,
            'categoria_financeira_id' => $payable->categoria_financeira_id,
            'user_id' => auth()->id(),
            'tipo' => 'saida',
            'origem' => 'conta_pagar',
            'origem_id' => $payable->id,
            'descricao' => $payable->descricao,
            'valor_cents' => $payable->valor_cents,
            'data_movimento' => $payable->pago_em,
            'forma_pagamento' => $payable->forma_pagamento,
        ]);
    }
}
