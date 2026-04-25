<?php

namespace App\Http\Controllers;

use App\Models\Clinic;
use Illuminate\Http\Request;

class ClinicController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Clinic::class, 'clinic');
    }

    public function index(Request $request)
    {
        $companyId = $request->session()->get('active_company_id');
        if (! $companyId) {
            return collect();
        }

        return Clinic::where('company_id', $companyId)->get();
    }

    public function show(Clinic $clinic)
    {
        return $clinic->load('units', 'services');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'legal_name' => ['nullable', 'string', 'max:255'],
            'cnpj' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
        ]);

        $companyId = $request->session()->get('active_company_id');
        if (! $companyId) {
            abort(403);
        }

        $clinic = Clinic::create(array_merge($data, [
            'company_id' => $companyId,
        ]));

        return $clinic;
    }

    public function update(Request $request, Clinic $clinic)
    {
        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'legal_name' => ['nullable', 'string', 'max:255'],
            'cnpj' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'active' => ['boolean'],
        ]);

        $clinic->update($data);

        return $clinic;
    }

    public function destroy(Clinic $clinic)
    {
        $clinic->delete();

        return response()->noContent();
    }
}
