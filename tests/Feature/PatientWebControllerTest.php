<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Clinic;
use App\Models\Company;
use App\Models\Patient;
use App\Models\Service;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PatientWebControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_patient_can_be_created_without_email_or_placeholder_user(): void
    {
        $company = Company::create(['name' => 'Clinica Teste']);
        $admin = User::factory()->create(['is_platform_admin' => true]);

        $response = $this
            ->actingAs($admin)
            ->withSession(['active_company_id' => $company->id])
            ->post(route('patients.store'), [
                'full_name' => 'Cliente Sem Email',
                'email' => '',
            ]);

        $response->assertRedirect(route('patients.index', absolute: false));

        $patient = Patient::where('full_name', 'Cliente Sem Email')->firstOrFail();
        $this->assertNull($patient->user_id);
        $this->assertFalse(User::where('email', 'like', 'cliente.%@example.invalid')->exists());
    }

    public function test_clearing_patient_email_removes_linked_user(): void
    {
        $company = Company::create(['name' => 'Clinica Teste']);
        $admin = User::factory()->create(['is_platform_admin' => true]);
        $patientUser = User::create([
            'name' => 'Cliente Com Email',
            'email' => 'cliente@example.com',
            'password' => Hash::make('password'),
        ]);
        $patient = Patient::create([
            'user_id' => $patientUser->id,
            'full_name' => 'Cliente Com Email',
        ]);
        $patient->companies()->attach($company);

        $response = $this
            ->actingAs($admin)
            ->withSession(['active_company_id' => $company->id])
            ->put(route('patients.update', $patient), [
                'full_name' => 'Cliente Com Email',
                'email' => '',
            ]);

        $response->assertRedirect(route('patients.index', absolute: false));

        $this->assertNull($patient->fresh()->user_id);
        $this->assertModelMissing($patientUser);
    }

    public function test_patient_with_appointment_is_detached_from_company_without_physical_delete(): void
    {
        $company = Company::create(['name' => 'Clinica Teste']);
        $clinic = Clinic::create(['company_id' => $company->id, 'name' => 'Clinica Teste']);
        $unit = Unit::create([
            'clinic_id' => $clinic->id,
            'name' => 'Unidade Central',
            'address_line1' => 'Rua Teste, 123',
            'city' => 'Belo Horizonte',
            'state' => 'MG',
            'zip' => '30100-000',
            'country' => 'BR',
        ]);
        $service = Service::create([
            'clinic_id' => $clinic->id,
            'unit_id' => $unit->id,
            'name' => 'Consulta',
            'duration_minutes' => 30,
            'modality' => 'presencial',
            'price_cents' => 10000,
            'active' => true,
        ]);
        $admin = User::factory()->create(['is_platform_admin' => true]);
        $patient = Patient::create(['full_name' => 'Cliente Com Historico']);
        $patient->companies()->attach($company);

        Appointment::create([
            'clinic_id' => $clinic->id,
            'unit_id' => $unit->id,
            'patient_id' => $patient->id,
            'service_id' => $service->id,
            'status' => 'scheduled',
            'channel' => 'manual',
            'scheduled_at' => now()->addDay(),
        ]);

        $response = $this
            ->actingAs($admin)
            ->withSession(['active_company_id' => $company->id])
            ->delete(route('patients.destroy', $patient));

        $response->assertRedirect(route('patients.index', absolute: false));
        $this->assertModelExists($patient);
        $this->assertFalse($patient->fresh()->companies()->whereKey($company->id)->exists());
    }
}
