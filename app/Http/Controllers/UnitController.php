<?php

namespace App\Http\Controllers;

use App\Models\Clinic;
use App\Models\Unit;
use Illuminate\Http\Request;

class UnitController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Unit::class, 'unit');
    }

    public function index(Request $request)
    {
        $companyId = $request->session()->get('active_company_id');
        if (! $companyId) {
            return collect();
        }

        $clinicIds = Clinic::where('company_id', $companyId)->pluck('id');

        return Unit::whereIn('clinic_id', $clinicIds)->get();
    }

    public function show(Unit $unit)
    {
        return $unit->load('clinic', 'professionals');
    }

    public function store(Request $request)
    {
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
        ]);

        $companyId = $request->session()->get('active_company_id');
        if (! $companyId || ! Clinic::where('company_id', $companyId)->whereKey($data['clinic_id'])->exists()) {
            abort(403);
        }

        return Unit::create($data);
    }

    public function update(Request $request, Unit $unit)
    {
        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'address_line1' => ['sometimes', 'required', 'string', 'max:255'],
            'address_line2' => ['nullable', 'string', 'max:255'],
            'city' => ['sometimes', 'required', 'string', 'max:255'],
            'state' => ['sometimes', 'required', 'string', 'size:2'],
            'zip' => ['sometimes', 'required', 'string', 'max:12'],
            'country' => ['nullable', 'string', 'size:2'],
            'phone' => ['nullable', 'string', 'max:30'],
            'active' => ['boolean'],
        ]);

        $unit->update($data);

        return $unit;
    }

    public function destroy(Unit $unit)
    {
        $unit->delete();

        return response()->noContent();
    }
}
