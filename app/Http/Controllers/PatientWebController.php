<?php

namespace App\Http\Controllers;

use App\Models\Clinic;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PatientWebController extends Controller
{
    public function index(Request $request): View
    {
        $companyId = $request->session()->get('active_company_id');
        if (! $companyId) {
            abort(403);
        }

        $clinicIds = Clinic::where('company_id', $companyId)->pluck('id');
        $query = Patient::query()
            ->whereHas('companies', function ($companyQuery) use ($companyId) {
                $companyQuery->where('companies.id', $companyId);
            })
            ->withMax([
                'appointments as last_appointment_at' => function ($appointmentQuery) use ($clinicIds) {
                    $appointmentQuery->whereIn('clinic_id', $clinicIds);
                },
            ], 'scheduled_at')
            ->orderBy('full_name');

        if ($request->filled('search')) {
            $search = $request->string('search')->trim()->toString();
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                    ->orWhere('cpf', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('cellphone', 'like', "%{$search}%")
                    ->orWhere('insurance_plan', 'like', "%{$search}%");
            });
        }

        $perPage = $request->string('per_page', '10')->toString();
        if (! in_array($perPage, ['10', '20', '50', 'all'], true)) {
            $perPage = '10';
        }

        $patients = $perPage === 'all'
            ? $query->get()
            : $query->paginate((int) $perPage)->withQueryString();

        return view('patients.index', [
            'patients' => $patients,
            'perPage' => $perPage,
        ]);
    }

    public function create(Request $request): View
    {
        $companyId = $request->session()->get('active_company_id');
        if (! $companyId) {
            abort(403);
        }

        return view('patients.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $companyId = $request->session()->get('active_company_id');
        if (! $companyId) {
            abort(403);
        }

        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', 'unique:users,email'],
            'cpf' => ['nullable', 'string', 'max:20', 'unique:patients,cpf'],
            'social_name' => ['nullable', 'string', 'max:255'],
            'rg' => ['nullable', 'string', 'max:30'],
            'rg_issuer' => ['nullable', 'string', 'max:50'],
            'rg_state' => ['nullable', 'string', 'size:2'],
            'cns' => ['nullable', 'string', 'max:30'],
            'passport' => ['nullable', 'string', 'max:30'],
            'birthdate' => ['nullable', 'date'],
            'gender' => ['nullable', 'in:masculino,feminino,outro'],
            'gender_identity' => ['nullable', 'string', 'max:50'],
            'marital_status' => ['nullable', 'string', 'max:50'],
            'nationality' => ['nullable', 'string', 'max:50'],
            'birthplace' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'string', 'max:20'],
            'photo' => ['nullable', 'image', 'max:2048'],
            'phone' => ['nullable', 'string', 'max:30'],
            'cellphone' => ['nullable', 'string', 'max:30'],
            'whatsapp' => ['nullable', 'boolean'],
            'emergency_contact_name' => ['nullable', 'string', 'max:255'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:30'],
            'emergency_contact_relationship' => ['nullable', 'string', 'max:50'],
            'mother_name' => ['nullable', 'string', 'max:255'],
            'father_name' => ['nullable', 'string', 'max:255'],
            'legal_guardian_name' => ['nullable', 'string', 'max:255'],
            'legal_guardian_cpf' => ['nullable', 'string', 'max:20'],
            'legal_guardian_phone' => ['nullable', 'string', 'max:30'],
            'guardian_relationship' => ['nullable', 'string', 'max:50'],
            'insurance_plan' => ['nullable', 'string', 'max:255'],
            'has_insurance' => ['nullable', 'boolean'],
            'insurance_name' => ['nullable', 'string', 'max:255'],
            'insurance_card_number' => ['nullable', 'string', 'max:50'],
            'insurance_plan_name' => ['nullable', 'string', 'max:255'],
            'insurance_card_valid_until' => ['nullable', 'date'],
            'insurance_accommodation' => ['nullable', 'string', 'max:100'],
            'insurance_holder' => ['nullable', 'boolean'],
            'address_zip' => ['nullable', 'string', 'max:12'],
            'address_street' => ['nullable', 'string', 'max:255'],
            'address_number' => ['nullable', 'string', 'max:20'],
            'address_complement' => ['nullable', 'string', 'max:255'],
            'address_district' => ['nullable', 'string', 'max:255'],
            'address_city' => ['nullable', 'string', 'max:255'],
            'address_state' => ['nullable', 'string', 'size:2'],
            'address_country' => ['nullable', 'string', 'size:2'],
            'blood_type' => ['nullable', 'string', 'max:10'],
            'weight_kg' => ['nullable', 'numeric'],
            'height_cm' => ['nullable', 'numeric'],
            'blood_pressure' => ['nullable', 'string', 'max:20'],
            'heart_rate' => ['nullable', 'string', 'max:20'],
            'respiratory_rate' => ['nullable', 'string', 'max:20'],
            'temperature' => ['nullable', 'string', 'max:20'],
            'preexisting_conditions' => ['nullable', 'string'],
            'chronic_conditions' => ['nullable', 'string'],
            'allergies' => ['nullable', 'string'],
            'current_medications' => ['nullable', 'string'],
            'previous_surgeries' => ['nullable', 'string'],
            'previous_hospitalizations' => ['nullable', 'string'],
            'family_history' => ['nullable', 'string'],
            'clinical_notes' => ['nullable', 'string'],
            'smoker' => ['nullable', 'string', 'max:30'],
            'alcohol_use' => ['nullable', 'string', 'max:30'],
            'physical_activity' => ['nullable', 'string', 'max:100'],
            'diet' => ['nullable', 'string', 'max:100'],
            'drug_use' => ['nullable', 'string', 'max:100'],
            'sleep_quality' => ['nullable', 'string', 'max:100'],
            'psych_diagnosis' => ['nullable', 'string', 'max:255'],
            'psych_followup' => ['nullable', 'string', 'max:255'],
            'controlled_medication' => ['nullable', 'boolean'],
            'suicide_history' => ['nullable', 'boolean'],
            'pregnant' => ['nullable', 'boolean'],
            'gestational_age' => ['nullable', 'string', 'max:50'],
            'pregnancies_count' => ['nullable', 'string', 'max:20'],
            'births_count' => ['nullable', 'string', 'max:20'],
            'abortions_count' => ['nullable', 'string', 'max:20'],
            'last_menstrual_period' => ['nullable', 'date'],
            'contraceptive_use' => ['nullable', 'string', 'max:100'],
            'medical_record_number' => ['nullable', 'string', 'max:50'],
            'service_unit' => ['nullable', 'string', 'max:255'],
            'responsible_doctor' => ['nullable', 'string', 'max:255'],
            'specialty' => ['nullable', 'string', 'max:255'],
            'admin_notes' => ['nullable', 'string'],
            'attachments_documents' => ['nullable', 'string'],
            'attachments_exams' => ['nullable', 'string'],
            'attachments_prescriptions' => ['nullable', 'string'],
            'attachments_reports' => ['nullable', 'string'],
            'attachments_certificates' => ['nullable', 'string'],
        ]);

        $data['whatsapp'] = (bool) ($data['whatsapp'] ?? false);
        $data['has_insurance'] = (bool) ($data['has_insurance'] ?? false);
        $data['insurance_holder'] = (bool) ($data['insurance_holder'] ?? false);
        $data['controlled_medication'] = (bool) ($data['controlled_medication'] ?? false);
        $data['suicide_history'] = (bool) ($data['suicide_history'] ?? false);
        $data['pregnant'] = (bool) ($data['pregnant'] ?? false);

        if ($request->hasFile('photo')) {
            $data['photo_path'] = $request->file('photo')->store('patients', 'public');
        }

        $email = $data['email'] ?? null;
        if (! $email) {
            $email = 'cliente.'.Str::uuid()->toString().'@example.invalid';
        }

        $user = User::create([
            'name' => $data['full_name'],
            'email' => $email,
            'password' => Hash::make(Str::random(16)),
        ]);

        $patient = Patient::create(array_merge(
            collect($data)->except('email')->toArray(),
            [
                'user_id' => $user->id,
                'created_by_name' => $request->user()?->name,
            ]
        ));

        $patient->companies()->syncWithoutDetaching([$companyId]);

        return redirect()->route('patients.index')->with('status', 'Cliente criado.');
    }

    public function edit(Request $request, Patient $patient): View
    {
        $companyId = $request->session()->get('active_company_id');
        if (! $companyId || ! $patient->companies()->whereKey($companyId)->exists()) {
            abort(403);
        }

        $clinicIds = Clinic::where('company_id', $companyId)->pluck('id');
        $appointments = $patient->appointments()
            ->whereIn('clinic_id', $clinicIds)
            ->with(['clinic', 'unit', 'professional', 'service', 'medicalRecord'])
            ->orderByDesc('scheduled_at')
            ->get();

        return view('patients.edit', [
            'patient' => $patient->load('user'),
            'appointments' => $appointments,
        ]);
    }

    public function update(Request $request, Patient $patient): RedirectResponse
    {
        $companyId = $request->session()->get('active_company_id');
        if (! $companyId || ! $patient->companies()->whereKey($companyId)->exists()) {
            abort(403);
        }

        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', 'unique:users,email,'.$patient->user_id],
            'cpf' => ['nullable', 'string', 'max:20', 'unique:patients,cpf,'.$patient->id],
            'social_name' => ['nullable', 'string', 'max:255'],
            'rg' => ['nullable', 'string', 'max:30'],
            'rg_issuer' => ['nullable', 'string', 'max:50'],
            'rg_state' => ['nullable', 'string', 'size:2'],
            'cns' => ['nullable', 'string', 'max:30'],
            'passport' => ['nullable', 'string', 'max:30'],
            'birthdate' => ['nullable', 'date'],
            'gender' => ['nullable', 'in:masculino,feminino,outro'],
            'gender_identity' => ['nullable', 'string', 'max:50'],
            'marital_status' => ['nullable', 'string', 'max:50'],
            'nationality' => ['nullable', 'string', 'max:50'],
            'birthplace' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'string', 'max:20'],
            'photo' => ['nullable', 'image', 'max:2048'],
            'phone' => ['nullable', 'string', 'max:30'],
            'cellphone' => ['nullable', 'string', 'max:30'],
            'whatsapp' => ['nullable', 'boolean'],
            'emergency_contact_name' => ['nullable', 'string', 'max:255'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:30'],
            'emergency_contact_relationship' => ['nullable', 'string', 'max:50'],
            'mother_name' => ['nullable', 'string', 'max:255'],
            'father_name' => ['nullable', 'string', 'max:255'],
            'legal_guardian_name' => ['nullable', 'string', 'max:255'],
            'legal_guardian_cpf' => ['nullable', 'string', 'max:20'],
            'legal_guardian_phone' => ['nullable', 'string', 'max:30'],
            'guardian_relationship' => ['nullable', 'string', 'max:50'],
            'insurance_plan' => ['nullable', 'string', 'max:255'],
            'has_insurance' => ['nullable', 'boolean'],
            'insurance_name' => ['nullable', 'string', 'max:255'],
            'insurance_card_number' => ['nullable', 'string', 'max:50'],
            'insurance_plan_name' => ['nullable', 'string', 'max:255'],
            'insurance_card_valid_until' => ['nullable', 'date'],
            'insurance_accommodation' => ['nullable', 'string', 'max:100'],
            'insurance_holder' => ['nullable', 'boolean'],
            'address_zip' => ['nullable', 'string', 'max:12'],
            'address_street' => ['nullable', 'string', 'max:255'],
            'address_number' => ['nullable', 'string', 'max:20'],
            'address_complement' => ['nullable', 'string', 'max:255'],
            'address_district' => ['nullable', 'string', 'max:255'],
            'address_city' => ['nullable', 'string', 'max:255'],
            'address_state' => ['nullable', 'string', 'size:2'],
            'address_country' => ['nullable', 'string', 'size:2'],
            'blood_type' => ['nullable', 'string', 'max:10'],
            'weight_kg' => ['nullable', 'numeric'],
            'height_cm' => ['nullable', 'numeric'],
            'blood_pressure' => ['nullable', 'string', 'max:20'],
            'heart_rate' => ['nullable', 'string', 'max:20'],
            'respiratory_rate' => ['nullable', 'string', 'max:20'],
            'temperature' => ['nullable', 'string', 'max:20'],
            'preexisting_conditions' => ['nullable', 'string'],
            'chronic_conditions' => ['nullable', 'string'],
            'allergies' => ['nullable', 'string'],
            'current_medications' => ['nullable', 'string'],
            'previous_surgeries' => ['nullable', 'string'],
            'previous_hospitalizations' => ['nullable', 'string'],
            'family_history' => ['nullable', 'string'],
            'clinical_notes' => ['nullable', 'string'],
            'smoker' => ['nullable', 'string', 'max:30'],
            'alcohol_use' => ['nullable', 'string', 'max:30'],
            'physical_activity' => ['nullable', 'string', 'max:100'],
            'diet' => ['nullable', 'string', 'max:100'],
            'drug_use' => ['nullable', 'string', 'max:100'],
            'sleep_quality' => ['nullable', 'string', 'max:100'],
            'psych_diagnosis' => ['nullable', 'string', 'max:255'],
            'psych_followup' => ['nullable', 'string', 'max:255'],
            'controlled_medication' => ['nullable', 'boolean'],
            'suicide_history' => ['nullable', 'boolean'],
            'pregnant' => ['nullable', 'boolean'],
            'gestational_age' => ['nullable', 'string', 'max:50'],
            'pregnancies_count' => ['nullable', 'string', 'max:20'],
            'births_count' => ['nullable', 'string', 'max:20'],
            'abortions_count' => ['nullable', 'string', 'max:20'],
            'last_menstrual_period' => ['nullable', 'date'],
            'contraceptive_use' => ['nullable', 'string', 'max:100'],
            'medical_record_number' => ['nullable', 'string', 'max:50'],
            'service_unit' => ['nullable', 'string', 'max:255'],
            'responsible_doctor' => ['nullable', 'string', 'max:255'],
            'specialty' => ['nullable', 'string', 'max:255'],
            'admin_notes' => ['nullable', 'string'],
            'attachments_documents' => ['nullable', 'string'],
            'attachments_exams' => ['nullable', 'string'],
            'attachments_prescriptions' => ['nullable', 'string'],
            'attachments_reports' => ['nullable', 'string'],
            'attachments_certificates' => ['nullable', 'string'],
        ]);

        $data['whatsapp'] = (bool) ($data['whatsapp'] ?? false);
        $data['has_insurance'] = (bool) ($data['has_insurance'] ?? false);
        $data['insurance_holder'] = (bool) ($data['insurance_holder'] ?? false);
        $data['controlled_medication'] = (bool) ($data['controlled_medication'] ?? false);
        $data['suicide_history'] = (bool) ($data['suicide_history'] ?? false);
        $data['pregnant'] = (bool) ($data['pregnant'] ?? false);

        if ($request->hasFile('photo')) {
            if ($patient->photo_path) {
                Storage::disk('public')->delete($patient->photo_path);
            }
            $data['photo_path'] = $request->file('photo')->store('patients', 'public');
        }

        $patient->update(collect($data)->except('email')->toArray());

        if ($patient->user && ! empty($data['email'])) {
            $patient->user->update([
                'name' => $data['full_name'],
                'email' => $data['email'],
            ]);
        }

        return redirect()->route('patients.index')->with('status', 'Cliente atualizado.');
    }

    public function destroy(Request $request, Patient $patient): RedirectResponse
    {
        $companyId = $request->session()->get('active_company_id');
        if (! $companyId || ! $patient->companies()->whereKey($companyId)->exists()) {
            abort(403);
        }

        $patient->companies()->detach($companyId);

        if (! $patient->companies()->exists()) {
            $patient->user?->delete();
            $patient->delete();
        }

        return redirect()->route('patients.index')->with('status', 'Cliente removido.');
    }
}
