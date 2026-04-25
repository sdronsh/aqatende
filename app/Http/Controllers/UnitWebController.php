<?php

namespace App\Http\Controllers;

use App\Models\Clinic;
use App\Models\Specialty;
use App\Models\Unit;
use App\Services\Licenses\LicenseEnforcer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UnitWebController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Unit::class);

        $companyId = $request->session()->get('active_company_id');
        if (! $companyId) {
            abort(403);
        }

        $clinicIds = Clinic::where('company_id', $companyId)->pluck('id');
        $query = Unit::with(['clinic', 'specialties'])
            ->whereIn('clinic_id', $clinicIds)
            ->orderBy('name');

        if ($request->filled('search')) {
            $search = $request->string('search')->trim()->toString();
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('city', 'like', "%{$search}%")
                    ->orWhereHas('clinic', function ($clinicQuery) use ($search) {
                        $clinicQuery->where('name', 'like', "%{$search}%");
                    });
            });
        }

        $perPage = $request->string('per_page', '10')->toString();
        if (! in_array($perPage, ['10', '20', '50', 'all'], true)) {
            $perPage = '10';
        }

        $units = $perPage === 'all'
            ? $query->get()
            : $query->paginate((int) $perPage)->withQueryString();

        return view('units.index', [
            'units' => $units,
            'perPage' => $perPage,
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Unit::class);

        $companyId = $request->session()->get('active_company_id');
        if (! $companyId) {
            abort(403);
        }

        $clinics = Clinic::where('company_id', $companyId)->orderBy('name')->get();
        $specialties = Specialty::where('company_id', $companyId)
            ->where('active', true)
            ->orderBy('name')
            ->get();

        return view('units.create', [
            'clinics' => $clinics,
            'specialties' => $specialties,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Unit::class);

        $companyId = $request->session()->get('active_company_id');
        if (! $companyId) {
            abort(403);
        }

        $limitError = app(LicenseEnforcer::class)->canCreateUnit($companyId);
        if ($limitError) {
            return back()->withErrors([
                'name' => $limitError,
            ])->withInput();
        }

        $data = $request->validate([
            'clinic_id' => ['required', 'integer', 'exists:clinics,id'],
            'name' => ['required', 'string', 'max:255'],
            'address_line1' => ['required', 'string', 'max:255'],
            'address_line2' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'state' => ['required', 'string', 'size:2'],
            'zip' => ['required', 'string', 'max:12'],
            'country' => ['nullable', 'string', 'size:2'],
            'phone' => ['nullable', 'string', 'max:30'],
            'active' => ['nullable', 'boolean'],
            'specialties' => ['array'],
            'specialties.*' => ['integer', Rule::exists('specialties', 'id')->where('company_id', $companyId)],
        ]);

        if (! Clinic::where('company_id', $companyId)->whereKey($data['clinic_id'])->exists()) {
            abort(403);
        }

        $unit = Unit::create(collect($data)->except('specialties')->toArray());
        $unit->specialties()->sync($data['specialties'] ?? []);

        return redirect()->route('units.index')->with('status', 'Unidade criada.');
    }

    public function edit(Request $request, Unit $unit): View
    {
        $this->authorize('update', $unit);

        $companyId = $request->session()->get('active_company_id');
        if (! $companyId || $unit->clinic?->company_id !== $companyId) {
            abort(403);
        }

        $clinics = Clinic::where('company_id', $companyId)->orderBy('name')->get();
        $specialties = Specialty::where('company_id', $companyId)
            ->where('active', true)
            ->orderBy('name')
            ->get();

        return view('units.edit', [
            'unit' => $unit->load('specialties'),
            'clinics' => $clinics,
            'specialties' => $specialties,
        ]);
    }

    public function update(Request $request, Unit $unit): RedirectResponse
    {
        $this->authorize('update', $unit);

        $companyId = $request->session()->get('active_company_id');
        if (! $companyId || $unit->clinic?->company_id !== $companyId) {
            abort(403);
        }

        $data = $request->validate([
            'clinic_id' => ['required', 'integer', 'exists:clinics,id'],
            'name' => ['required', 'string', 'max:255'],
            'address_line1' => ['required', 'string', 'max:255'],
            'address_line2' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'state' => ['required', 'string', 'size:2'],
            'zip' => ['required', 'string', 'max:12'],
            'country' => ['nullable', 'string', 'size:2'],
            'phone' => ['nullable', 'string', 'max:30'],
            'active' => ['nullable', 'boolean'],
            'specialties' => ['array'],
            'specialties.*' => ['integer', Rule::exists('specialties', 'id')->where('company_id', $companyId)],
        ]);

        if (! Clinic::where('company_id', $companyId)->whereKey($data['clinic_id'])->exists()) {
            abort(403);
        }

        $unit->update(collect($data)->except('specialties')->toArray());
        $unit->specialties()->sync($data['specialties'] ?? []);

        return redirect()->route('units.index')->with('status', 'Unidade atualizada.');
    }

    public function destroy(Request $request, Unit $unit): RedirectResponse
    {
        $this->authorize('delete', $unit);

        $companyId = $request->session()->get('active_company_id');
        if (! $companyId || $unit->clinic?->company_id !== $companyId) {
            abort(403);
        }

        $unit->delete();

        return redirect()->route('units.index')->with('status', 'Unidade removida.');
    }
}
