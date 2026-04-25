<?php

namespace App\Http\Controllers;

use App\Models\Specialty;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SpecialtyWebController extends Controller
{
    public function index(Request $request): View
    {
        $companyId = $request->session()->get('active_company_id');
        if (! $companyId) {
            abort(403);
        }

        $query = Specialty::query()
            ->where('company_id', $companyId)
            ->orderBy('name');

        if ($request->filled('search')) {
            $search = $request->string('search')->trim()->toString();
            $query->where('name', 'like', "%{$search}%");
        }

        $perPage = $request->string('per_page', '10')->toString();
        if (! in_array($perPage, ['10', '20', '50', 'all'], true)) {
            $perPage = '10';
        }

        $specialties = $perPage === 'all'
            ? $query->get()
            : $query->paginate((int) $perPage)->withQueryString();

        return view('specialties.index', [
            'specialties' => $specialties,
            'perPage' => $perPage,
        ]);
    }

    public function create(): View
    {
        return view('specialties.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $companyId = $request->session()->get('active_company_id');
        if (! $companyId) {
            abort(403);
        }

        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('specialties', 'name')->where('company_id', $companyId),
            ],
            'active' => ['nullable', 'boolean'],
        ]);

        Specialty::create([
            'company_id' => $companyId,
            'name' => $data['name'],
            'active' => (bool) ($data['active'] ?? false),
        ]);

        return redirect()->route('specialties.index')->with('status', 'Especialidade criada.');
    }

    public function edit(Request $request, Specialty $specialty): View
    {
        if ($specialty->company_id !== $request->session()->get('active_company_id')) {
            abort(403);
        }

        return view('specialties.edit', [
            'specialty' => $specialty,
        ]);
    }

    public function update(Request $request, Specialty $specialty): RedirectResponse
    {
        $companyId = $request->session()->get('active_company_id');
        if (! $companyId || $specialty->company_id !== $companyId) {
            abort(403);
        }

        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('specialties', 'name')
                    ->where('company_id', $companyId)
                    ->ignore($specialty->id),
            ],
            'active' => ['nullable', 'boolean'],
        ]);

        $specialty->update([
            'name' => $data['name'],
            'active' => (bool) ($data['active'] ?? false),
        ]);

        return redirect()->route('specialties.index')->with('status', 'Especialidade atualizada.');
    }

    public function destroy(Request $request, Specialty $specialty): RedirectResponse
    {
        if ($specialty->company_id !== $request->session()->get('active_company_id')) {
            abort(403);
        }

        $specialty->delete();

        return redirect()->route('specialties.index')->with('status', 'Especialidade removida.');
    }
}
