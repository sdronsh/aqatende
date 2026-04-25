<?php

namespace App\Http\Controllers;

use App\Models\Clinic;
use App\Models\FinancialCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FinanceCategoryController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:financeiro.categorias.view')->only(['index']);
        $this->middleware('permission:financeiro.categorias.create')->only(['create', 'store']);
        $this->middleware('permission:financeiro.categorias.update')->only(['edit', 'update']);
    }

    public function index(Request $request): View
    {
        $clinicIds = $this->getClinicIds($request);
        $selectedClinicId = $request->integer('clinic_id') ?: null;

        $query = FinancialCategory::query()
            ->whereIn('clinic_id', $clinicIds)
            ->with('clinic')
            ->orderBy('name');

        if ($selectedClinicId && in_array($selectedClinicId, $clinicIds, true)) {
            $query->where('clinic_id', $selectedClinicId);
        }

        if ($request->filled('search')) {
            $search = $request->string('search')->trim()->toString();
            $query->where('name', 'like', "%{$search}%");
        }

        $perPage = $request->string('per_page', '10')->toString();
        if (! in_array($perPage, ['10', '20', '50', 'all'], true)) {
            $perPage = '10';
        }

        $categories = $perPage === 'all'
            ? $query->get()
            : $query->paginate((int) $perPage)->withQueryString();

        $clinics = Clinic::whereIn('id', $clinicIds)->orderBy('name')->get();

        return view('finance/categories/index', [
            'categories' => $categories,
            'clinics' => $clinics,
            'perPage' => $perPage,
            'selectedClinicId' => $selectedClinicId,
        ]);
    }

    public function create(Request $request): View
    {
        $clinicIds = $this->getClinicIds($request);

        return view('finance/categories/create', [
            'clinics' => Clinic::whereIn('id', $clinicIds)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $clinicIds = $this->getClinicIds($request);

        $data = $request->validate([
            'clinic_id' => ['required', 'integer', 'exists:clinics,id'],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'max:20'],
            'active' => ['nullable', 'boolean'],
        ]);

        if (! in_array((int) $data['clinic_id'], $clinicIds, true)) {
            abort(403);
        }

        $data['active'] = (bool) ($data['active'] ?? true);

        FinancialCategory::create($data);

        return redirect()->route('finance.categories.index')->with('status', 'Categoria criada.');
    }

    public function edit(Request $request, FinancialCategory $category): View
    {
        $clinicIds = $this->getClinicIds($request);
        if (! in_array($category->clinic_id, $clinicIds, true)) {
            abort(403);
        }

        return view('finance/categories/edit', [
            'category' => $category,
            'clinics' => Clinic::whereIn('id', $clinicIds)->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, FinancialCategory $category): RedirectResponse
    {
        $clinicIds = $this->getClinicIds($request);
        if (! in_array($category->clinic_id, $clinicIds, true)) {
            abort(403);
        }

        $data = $request->validate([
            'clinic_id' => ['required', 'integer', 'exists:clinics,id'],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'max:20'],
            'active' => ['nullable', 'boolean'],
        ]);

        if (! in_array((int) $data['clinic_id'], $clinicIds, true)) {
            abort(403);
        }

        $data['active'] = (bool) ($data['active'] ?? true);

        $category->update($data);

        return redirect()->route('finance.categories.index')->with('status', 'Categoria atualizada.');
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
