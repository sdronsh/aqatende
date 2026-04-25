<?php

namespace App\Http\Controllers;

use App\Models\AccountReceivable;
use App\Models\Appointment;
use App\Models\Clinic;
use App\Models\FinancialCategory;
use App\Models\Patient;
use App\Models\Professional;
use App\Models\Unit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FinanceReceivableController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:financeiro.contas_receber.view')->only(['index']);
        $this->middleware('permission:financeiro.contas_receber.create')->only(['create', 'store']);
        $this->middleware('permission:financeiro.contas_receber.update')->only(['edit', 'update']);
    }

    public function index(Request $request): View
    {
        $clinicIds = $this->getClinicIds($request);
        $selectedClinicId = $request->integer('clinic_id') ?: null;

        $query = AccountReceivable::query()
            ->whereIn('clinic_id', $clinicIds)
            ->with(['patient', 'clinic', 'unit', 'category'])
            ->orderByDesc('data_vencimento');

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
                    ->orWhereHas('patient', function ($patientQuery) use ($search) {
                        $patientQuery->where('full_name', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('date_from')) {
            $query->whereDate('data_vencimento', '>=', $request->date('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('data_vencimento', '<=', $request->date('date_to'));
        }

        $perPage = $request->string('per_page', '10')->toString();
        if (! in_array($perPage, ['10', '20', '50', 'all'], true)) {
            $perPage = '10';
        }

        $receivables = $perPage === 'all'
            ? $query->get()
            : $query->paginate((int) $perPage)->withQueryString();

        $clinics = Clinic::whereIn('id', $clinicIds)->orderBy('name')->get();

        return view('finance/receivables/index', [
            'receivables' => $receivables,
            'clinics' => $clinics,
            'perPage' => $perPage,
            'selectedClinicId' => $selectedClinicId,
        ]);
    }

    public function create(Request $request): View
    {
        $clinicIds = $this->getClinicIds($request);

        return view('finance/receivables/create', [
            'clinics' => Clinic::whereIn('id', $clinicIds)->orderBy('name')->get(),
            'units' => Unit::whereIn('clinic_id', $clinicIds)->orderBy('name')->get(),
            'patients' => Patient::whereHas('companies', function ($query) use ($request) {
                $query->where('companies.id', $request->session()->get('active_company_id'));
            })->orderBy('full_name')->get(),
            'categories' => FinancialCategory::whereIn('clinic_id', $clinicIds)->orderBy('name')->get(),
            'professionals' => Professional::query()
                ->whereHas('user.companies', fn ($q) => $q->where('companies.id', $request->session()->get('active_company_id')))
                ->orderBy('display_name')
                ->get(),
            'appointments' => Appointment::whereIn('clinic_id', $clinicIds)->orderByDesc('scheduled_at')->limit(50)->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $clinicIds = $this->getClinicIds($request);

        $data = $request->validate([
            'clinic_id' => ['required', 'integer', 'exists:clinics,id'],
            'unit_id' => ['nullable', 'integer', 'exists:units,id'],
            'professional_id' => ['nullable', 'integer', 'exists:professionals,id'],
            'patient_id' => ['nullable', 'integer', 'exists:patients,id'],
            'appointment_id' => ['nullable', 'integer', 'exists:appointments,id'],
            'categoria_financeira_id' => ['nullable', 'integer', 'exists:categorias_financeiras,id'],
            'descricao' => ['required', 'string', 'max:255'],
            'valor_total' => $this->moneyRule(),
            'numero_parcelas' => ['nullable', 'integer', 'min:1', 'max:120'],
            'numero_parcela' => ['nullable', 'integer', 'min:1', 'max:120'],
            'valor_parcela' => $this->moneyRule(false),
            'data_emissao' => ['nullable', 'date'],
            'data_vencimento' => ['required', 'date'],
            'status' => ['nullable', 'string', 'max:20'],
            'forma_pagamento' => ['nullable', 'string', 'max:20'],
            'observacoes' => ['nullable', 'string'],
        ]);

        if (! in_array((int) $data['clinic_id'], $clinicIds, true)) {
            abort(403);
        }

        if (! empty($data['patient_id'])) {
            $patientOk = Patient::whereKey($data['patient_id'])
                ->whereHas('companies', fn ($q) => $q->where('companies.id', $request->session()->get('active_company_id')))
                ->exists();
            if (! $patientOk) {
                return back()->withErrors(['patient_id' => 'Cliente invalido para esta empresa.'])->withInput();
            }
        }

        if (! empty($data['appointment_id'])) {
            $appointment = Appointment::where('clinic_id', $data['clinic_id'])->find($data['appointment_id']);
            if (! $appointment) {
                return back()->withErrors(['appointment_id' => 'Atendimento invalido para a clinica selecionada.'])->withInput();
            }
            if (empty($data['patient_id'])) {
                $data['patient_id'] = $appointment->patient_id;
            }
            if (empty($data['professional_id'])) {
                $data['professional_id'] = $appointment->professional_id;
            }
            if (empty($data['categoria_financeira_id'])) {
                $data['categoria_financeira_id'] = $this->resolveReceivableCategoryId($appointment->clinic_id);
            }
        }

        $data['valor_total_cents'] = $this->parsePriceToCents($data['valor_total']);
        $data['valor_parcela_cents'] = isset($data['valor_parcela']) && $data['valor_parcela'] !== null
            ? $this->parsePriceToCents($data['valor_parcela'])
            : null;

        $data['numero_parcelas'] = $data['numero_parcelas'] ?? 1;
        $data['numero_parcela'] = $data['numero_parcela'] ?? 1;
        $data['status'] = $data['status'] ?? 'aberto';

        unset($data['valor_total'], $data['valor_parcela']);

        $receivable = AccountReceivable::create($data);
        $this->syncCashFlow($receivable, false);

        return redirect()->route('finance.receivables.index')->with('status', 'Conta a receber criada.');
    }

    public function edit(Request $request, AccountReceivable $receivable): View
    {
        $clinicIds = $this->getClinicIds($request);
        if (! in_array($receivable->clinic_id, $clinicIds, true)) {
            abort(403);
        }

        return view('finance/receivables/edit', [
            'receivable' => $receivable,
            'clinics' => Clinic::whereIn('id', $clinicIds)->orderBy('name')->get(),
            'units' => Unit::whereIn('clinic_id', $clinicIds)->orderBy('name')->get(),
            'patients' => Patient::whereHas('companies', function ($query) use ($request) {
                $query->where('companies.id', $request->session()->get('active_company_id'));
            })->orderBy('full_name')->get(),
            'categories' => FinancialCategory::whereIn('clinic_id', $clinicIds)->orderBy('name')->get(),
            'professionals' => Professional::query()
                ->whereHas('user.companies', fn ($q) => $q->where('companies.id', $request->session()->get('active_company_id')))
                ->orderBy('display_name')
                ->get(),
            'appointments' => Appointment::whereIn('clinic_id', $clinicIds)->orderByDesc('scheduled_at')->limit(50)->get(),
        ]);
    }

    public function update(Request $request, AccountReceivable $receivable): RedirectResponse
    {
        $clinicIds = $this->getClinicIds($request);
        if (! in_array($receivable->clinic_id, $clinicIds, true)) {
            abort(403);
        }

        $data = $request->validate([
            'clinic_id' => ['required', 'integer', 'exists:clinics,id'],
            'unit_id' => ['nullable', 'integer', 'exists:units,id'],
            'professional_id' => ['nullable', 'integer', 'exists:professionals,id'],
            'patient_id' => ['nullable', 'integer', 'exists:patients,id'],
            'appointment_id' => ['nullable', 'integer', 'exists:appointments,id'],
            'categoria_financeira_id' => ['nullable', 'integer', 'exists:categorias_financeiras,id'],
            'descricao' => ['required', 'string', 'max:255'],
            'valor_total' => $this->moneyRule(),
            'numero_parcelas' => ['nullable', 'integer', 'min:1', 'max:120'],
            'numero_parcela' => ['nullable', 'integer', 'min:1', 'max:120'],
            'valor_parcela' => $this->moneyRule(false),
            'data_emissao' => ['nullable', 'date'],
            'data_vencimento' => ['required', 'date'],
            'status' => ['nullable', 'string', 'max:20'],
            'forma_pagamento' => ['nullable', 'string', 'max:20'],
            'observacoes' => ['nullable', 'string'],
        ]);

        if (! in_array((int) $data['clinic_id'], $clinicIds, true)) {
            abort(403);
        }

        if (! empty($data['patient_id'])) {
            $patientOk = Patient::whereKey($data['patient_id'])
                ->whereHas('companies', fn ($q) => $q->where('companies.id', $request->session()->get('active_company_id')))
                ->exists();
            if (! $patientOk) {
                return back()->withErrors(['patient_id' => 'Cliente invalido para esta empresa.'])->withInput();
            }
        }

        if (! empty($data['appointment_id'])) {
            $appointment = Appointment::where('clinic_id', $data['clinic_id'])->find($data['appointment_id']);
            if (! $appointment) {
                return back()->withErrors(['appointment_id' => 'Atendimento invalido para a clinica selecionada.'])->withInput();
            }
            if (empty($data['patient_id'])) {
                $data['patient_id'] = $appointment->patient_id;
            }
            if (empty($data['professional_id'])) {
                $data['professional_id'] = $appointment->professional_id;
            }
            if (empty($data['categoria_financeira_id'])) {
                $data['categoria_financeira_id'] = $this->resolveReceivableCategoryId($appointment->clinic_id);
            }
        }

        $data['valor_total_cents'] = $this->parsePriceToCents($data['valor_total']);
        $data['valor_parcela_cents'] = isset($data['valor_parcela']) && $data['valor_parcela'] !== null
            ? $this->parsePriceToCents($data['valor_parcela'])
            : null;

        $data['numero_parcelas'] = $data['numero_parcelas'] ?? 1;
        $data['numero_parcela'] = $data['numero_parcela'] ?? 1;
        $data['status'] = $data['status'] ?? 'aberto';

        unset($data['valor_total'], $data['valor_parcela']);

        $wasPaid = $receivable->status === 'pago';
        $receivable->update($data);
        $this->syncCashFlow($receivable, $wasPaid);

        return redirect()->route('finance.receivables.index')->with('status', 'Conta a receber atualizada.');
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

    private function syncCashFlow(AccountReceivable $receivable, bool $wasPaid): void
    {
        if ($receivable->status !== 'pago') {
            return;
        }

        if (! $receivable->pago_em) {
            $receivable->pago_em = now();
            $receivable->save();
        }

        if ($wasPaid) {
            return;
        }

        $exists = \App\Models\CashFlowEntry::where('origem', 'conta_receber')
            ->where('origem_id', $receivable->id)
            ->exists();

        if ($exists) {
            return;
        }

        $professionalId = $receivable->professional_id;
        if (! $professionalId && $receivable->appointment?->professional_id) {
            $professionalId = $receivable->appointment->professional_id;
        }
        if (! $professionalId && $receivable->appointment_id) {
            $professionalId = Appointment::whereKey($receivable->appointment_id)->value('professional_id');
        }

        \App\Models\CashFlowEntry::create([
            'clinic_id' => $receivable->clinic_id,
            'unit_id' => $receivable->unit_id,
            'professional_id' => $professionalId,
            'categoria_financeira_id' => $receivable->categoria_financeira_id,
            'user_id' => auth()->id(),
            'tipo' => 'entrada',
            'origem' => 'conta_receber',
            'origem_id' => $receivable->id,
            'descricao' => $receivable->descricao,
            'valor_cents' => $receivable->valor_total_cents,
            'data_movimento' => $receivable->pago_em,
            'forma_pagamento' => $receivable->forma_pagamento,
        ]);
    }

    private function resolveReceivableCategoryId(int $clinicId): ?int
    {
        $category = FinancialCategory::query()
            ->where('clinic_id', $clinicId)
            ->where('type', 'receber')
            ->where(function ($query) {
                $query->where('name', 'like', 'Consulta%')
                    ->orWhere('name', 'like', 'Consultas%');
            })
            ->first();

        if ($category) {
            return $category->id;
        }

        return FinancialCategory::query()
            ->where('clinic_id', $clinicId)
            ->where('type', 'receber')
            ->orderBy('name')
            ->value('id');
    }
}
