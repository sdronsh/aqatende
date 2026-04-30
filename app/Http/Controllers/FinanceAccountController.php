<?php

namespace App\Http\Controllers;

use App\Models\Clinic;
use App\Models\FinancialAccount;
use App\Models\Unit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FinanceAccountController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:financeiro.contas_bancarias.view')->only(['index']);
        $this->middleware('permission:financeiro.contas_bancarias.create')->only(['create', 'store']);
        $this->middleware('permission:financeiro.contas_bancarias.update')->only(['edit', 'update']);
    }

    public function index(Request $request): View
    {
        $clinicIds = $this->getClinicIds($request);
        $selectedClinicId = $request->integer('clinic_id') ?: null;
        $selectedUnitId = $request->integer('unit_id') ?: null;

        $query = FinancialAccount::query()
            ->whereIn('clinic_id', $clinicIds)
            ->with(['clinic', 'unit']);

        if ($selectedClinicId && in_array($selectedClinicId, $clinicIds, true)) {
            $query->where('clinic_id', $selectedClinicId);
        }

        if ($selectedUnitId) {
            $query->where('unit_id', $selectedUnitId);
        }

        if ($request->filled('search')) {
            $search = $request->string('search')->trim()->toString();
            $query->where('name', 'like', "%{$search}%");
        }

        $orderBy = $request->string('order_by', 'name_asc')->toString();
        if (! in_array($orderBy, ['name_asc', 'name_desc', 'balance_desc', 'balance_asc'], true)) {
            $orderBy = 'name_asc';
        }

        match ($orderBy) {
            'name_desc' => $query->orderByDesc('name'),
            'balance_desc' => $query->orderByDesc('initial_balance_cents'),
            'balance_asc' => $query->orderBy('initial_balance_cents'),
            default => $query->orderBy('name'),
        };

        $perPage = $request->string('per_page', '10')->toString();
        if (! in_array($perPage, ['10', '20', '50', 'all'], true)) {
            $perPage = '10';
        }

        $accounts = $perPage === 'all'
            ? $query->get()
            : $query->paginate((int) $perPage)->withQueryString();

        $clinics = Clinic::whereIn('id', $clinicIds)->orderBy('name')->get();
        $units = Unit::whereIn('clinic_id', $clinicIds)->orderBy('name')->get();

        return view('finance/accounts/index', [
            'accounts' => $accounts,
            'clinics' => $clinics,
            'units' => $units,
            'perPage' => $perPage,
            'orderBy' => $orderBy,
            'selectedClinicId' => $selectedClinicId,
            'selectedUnitId' => $selectedUnitId,
        ]);
    }

    public function create(Request $request): View
    {
        $clinicIds = $this->getClinicIds($request);

        return view('finance/accounts/create', [
            'clinics' => Clinic::whereIn('id', $clinicIds)->orderBy('name')->get(),
            'units' => Unit::whereIn('clinic_id', $clinicIds)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $clinicIds = $this->getClinicIds($request);

        $data = $request->validate([
            'clinic_id' => ['required', 'integer', 'exists:clinics,id'],
            'unit_id' => ['required', 'integer', 'exists:units,id'],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'max:20'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'agency' => ['nullable', 'string', 'max:20'],
            'account_number' => ['nullable', 'string', 'max:30'],
            'pix_key' => ['nullable', 'string', 'max:255'],
            'initial_balance' => $this->moneyRule(),
            'active' => ['nullable', 'boolean'],
        ]);

        if (! in_array((int) $data['clinic_id'], $clinicIds, true)) {
            abort(403);
        }

        $unit = Unit::whereIn('clinic_id', $clinicIds)->find($data['unit_id']);
        if (! $unit || $unit->clinic_id !== (int) $data['clinic_id']) {
            return back()->withErrors(['unit_id' => 'Unidade invalida para a clinica selecionada.'])->withInput();
        }

        $data['initial_balance_cents'] = $this->parsePriceToCents($data['initial_balance'] ?? '0');
        $data['active'] = (bool) ($data['active'] ?? true);
        unset($data['initial_balance']);

        FinancialAccount::create($data);

        return redirect()->route('finance.accounts.index')->with('status', 'Conta bancaria criada.');
    }

    public function edit(Request $request, FinancialAccount $account): View
    {
        $clinicIds = $this->getClinicIds($request);
        if (! in_array($account->clinic_id, $clinicIds, true)) {
            abort(403);
        }

        return view('finance/accounts/edit', [
            'account' => $account,
            'clinics' => Clinic::whereIn('id', $clinicIds)->orderBy('name')->get(),
            'units' => Unit::whereIn('clinic_id', $clinicIds)->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, FinancialAccount $account): RedirectResponse
    {
        $clinicIds = $this->getClinicIds($request);
        if (! in_array($account->clinic_id, $clinicIds, true)) {
            abort(403);
        }

        $data = $request->validate([
            'clinic_id' => ['required', 'integer', 'exists:clinics,id'],
            'unit_id' => ['required', 'integer', 'exists:units,id'],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'max:20'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'agency' => ['nullable', 'string', 'max:20'],
            'account_number' => ['nullable', 'string', 'max:30'],
            'pix_key' => ['nullable', 'string', 'max:255'],
            'initial_balance' => $this->moneyRule(),
            'active' => ['nullable', 'boolean'],
        ]);

        if (! in_array((int) $data['clinic_id'], $clinicIds, true)) {
            abort(403);
        }

        $unit = Unit::whereIn('clinic_id', $clinicIds)->find($data['unit_id']);
        if (! $unit || $unit->clinic_id !== (int) $data['clinic_id']) {
            return back()->withErrors(['unit_id' => 'Unidade invalida para a clinica selecionada.'])->withInput();
        }

        $data['initial_balance_cents'] = $this->parsePriceToCents($data['initial_balance'] ?? '0');
        $data['active'] = (bool) ($data['active'] ?? true);
        unset($data['initial_balance']);

        $account->update($data);

        return redirect()->route('finance.accounts.index')->with('status', 'Conta bancaria atualizada.');
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

    private function moneyRule(bool $required = false): array
    {
        $rules = ['regex:/^\\d{1,3}(\\.\\d{3})*(,\\d{2})?$|^\\d+([.,]\\d{1,2})?$/'];
        array_unshift($rules, $required ? 'required' : 'nullable');

        return $rules;
    }
}
