<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Patient;
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
}
