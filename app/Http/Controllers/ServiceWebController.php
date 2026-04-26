<?php

namespace App\Http\Controllers;

use App\Models\Clinic;
use App\Models\Service;
use App\Models\Unit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ServiceWebController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Service::class);

        $companyId = $request->session()->get('active_company_id');
        if (! $companyId) {
            abort(403);
        }

        $clinicIds = Clinic::where('company_id', $companyId)->pluck('id');
        $query = Service::with(['clinic', 'unit'])
            ->whereIn('clinic_id', $clinicIds)
            ->orderBy('name');

        if ($request->filled('search')) {
            $search = $request->string('search')->trim()->toString();
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhereHas('clinic', function ($clinicQuery) use ($search) {
                        $clinicQuery->where('name', 'like', "%{$search}%");
                    });
            });
        }

        $perPage = $request->string('per_page', '10')->toString();
        if (! in_array($perPage, ['10', '20', '50', 'all'], true)) {
            $perPage = '10';
        }

        $services = $perPage === 'all'
            ? $query->get()
            : $query->paginate((int) $perPage)->withQueryString();

        return view('services.index', [
            'services' => $services,
            'perPage' => $perPage,
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Service::class);

        $companyId = $request->session()->get('active_company_id');
        if (! $companyId) {
            abort(403);
        }

        $clinics = Clinic::where('company_id', $companyId)->orderBy('name')->get();
        $units = Unit::whereIn('clinic_id', $clinics->pluck('id'))->orderBy('name')->get();

        return view('services.create', [
            'clinics' => $clinics,
            'units' => $units,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Service::class);

        $companyId = $request->session()->get('active_company_id');
        if (! $companyId) {
            abort(403);
        }

        $data = $request->validate([
            'clinic_id' => ['required', 'integer', 'exists:clinics,id'],
            'unit_id' => ['nullable', 'integer', 'exists:units,id'],
            'name' => ['required', 'string', 'max:255'],
            'duration_minutes' => ['required', 'integer', 'min:5', 'max:480'],
            'modality' => ['required', 'string', 'max:20'],
            'price' => $this->moneyRule('price_cents'),
            'price_cents' => ['required_without:price', 'nullable', 'integer', 'min:0'],
            'active' => ['nullable', 'boolean'],
            'shared_service' => ['nullable', 'boolean'],
        ]);

        if (! Clinic::where('company_id', $companyId)->whereKey($data['clinic_id'])->exists()) {
            abort(403);
        }

        if (! empty($data['unit_id'])) {
            $unitOk = Unit::whereKey($data['unit_id'])
                ->where('clinic_id', $data['clinic_id'])
                ->exists();
            if (! $unitOk) {
                return back()->withErrors([
                    'unit_id' => 'Unidade invalida para a clinica selecionada.',
                ])->withInput();
            }
        }

        $data['price_cents'] = $this->resolvePriceCents($data);
        $data['active'] = (bool) ($data['active'] ?? false);
        $data['shared_service'] = (bool) ($data['shared_service'] ?? false);
        unset($data['price']);

        Service::create($data);

        return redirect()->route('services.index')->with('status', 'Servico criado.');
    }

    public function edit(Request $request, Service $service): View
    {
        $this->authorize('update', $service);

        $companyId = $request->session()->get('active_company_id');
        if (! $companyId || $service->clinic?->company_id !== $companyId) {
            abort(403);
        }

        $clinics = Clinic::where('company_id', $companyId)->orderBy('name')->get();
        $units = Unit::whereIn('clinic_id', $clinics->pluck('id'))->orderBy('name')->get();

        return view('services.edit', [
            'service' => $service,
            'clinics' => $clinics,
            'units' => $units,
        ]);
    }

    public function update(Request $request, Service $service): RedirectResponse
    {
        $this->authorize('update', $service);

        $companyId = $request->session()->get('active_company_id');
        if (! $companyId || $service->clinic?->company_id !== $companyId) {
            abort(403);
        }

        $data = $request->validate([
            'clinic_id' => ['required', 'integer', 'exists:clinics,id'],
            'unit_id' => ['nullable', 'integer', 'exists:units,id'],
            'name' => ['required', 'string', 'max:255'],
            'duration_minutes' => ['required', 'integer', 'min:5', 'max:480'],
            'modality' => ['required', 'string', 'max:20'],
            'price' => $this->moneyRule('price_cents'),
            'price_cents' => ['required_without:price', 'nullable', 'integer', 'min:0'],
            'active' => ['nullable', 'boolean'],
            'shared_service' => ['nullable', 'boolean'],
        ]);

        if (! Clinic::where('company_id', $companyId)->whereKey($data['clinic_id'])->exists()) {
            abort(403);
        }

        if (! empty($data['unit_id'])) {
            $unitOk = Unit::whereKey($data['unit_id'])
                ->where('clinic_id', $data['clinic_id'])
                ->exists();
            if (! $unitOk) {
                return back()->withErrors([
                    'unit_id' => 'Unidade invalida para a clinica selecionada.',
                ])->withInput();
            }
        }

        $data['price_cents'] = $this->resolvePriceCents($data);
        $data['active'] = (bool) ($data['active'] ?? false);
        $data['shared_service'] = (bool) ($data['shared_service'] ?? false);
        unset($data['price']);

        $service->update($data);

        return redirect()->route('services.index')->with('status', 'Servico atualizado.');
    }

    public function destroy(Request $request, Service $service): RedirectResponse
    {
        $this->authorize('delete', $service);

        $companyId = $request->session()->get('active_company_id');
        if (! $companyId || $service->clinic?->company_id !== $companyId) {
            abort(403);
        }

        $service->delete();

        return redirect()->route('services.index')->with('status', 'Servico removido.');
    }

    private function resolvePriceCents(array $data): int
    {
        if (array_key_exists('price', $data) && $data['price'] !== null && $data['price'] !== '') {
            return $this->parsePriceToCents($data['price']);
        }

        if (array_key_exists('price_cents', $data) && $data['price_cents'] !== null && $data['price_cents'] !== '') {
            return (int) $data['price_cents'];
        }

        return 0;
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

    private function moneyRule(?string $requiredWithout = null): array
    {
        $rules = ['nullable', 'regex:/^\\d{1,3}(\\.\\d{3})*(,\\d{2})?$|^\\d+([.,]\\d{1,2})?$/'];
        if ($requiredWithout) {
            $rules[] = 'required_without:' . $requiredWithout;
        }

        return $rules;
    }
}
