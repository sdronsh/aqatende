<?php

namespace Tests\Feature;

use App\Models\AccountReceivable;
use App\Models\Appointment;
use App\Models\CashFlowEntry;
use App\Models\Clinic;
use App\Models\Company;
use App\Models\Patient;
use App\Models\Service;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QueueWebControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_finishing_queue_appointment_creates_paid_receivable_and_cashflow_entry(): void
    {
        $company = Company::create(['name' => 'Salao Teste']);
        $clinic = Clinic::create([
            'company_id' => $company->id,
            'name' => 'Unidade Principal',
            'terms_version' => '1.0',
            'terms_accepted_at' => now(),
        ]);
        $unit = Unit::create([
            'clinic_id' => $clinic->id,
            'name' => 'Sala 1',
            'address_line1' => 'Rua Teste',
            'city' => 'Sao Paulo',
            'state' => 'SP',
            'zip' => '01000-000',
        ]);
        $service = Service::create([
            'clinic_id' => $clinic->id,
            'unit_id' => $unit->id,
            'name' => 'Corte',
            'duration_minutes' => 30,
            'modality' => 'presencial',
            'price_cents' => 5000,
            'active' => true,
        ]);
        $patient = Patient::create(['full_name' => 'Cliente Teste']);
        $patient->companies()->attach($company);
        $admin = User::factory()->create(['is_platform_admin' => true]);

        $appointment = Appointment::create([
            'clinic_id' => $clinic->id,
            'unit_id' => $unit->id,
            'patient_id' => $patient->id,
            'service_id' => $service->id,
            'status' => 'in_progress',
            'channel' => 'walk_in',
            'started_at' => now(),
            'price_cents' => 5000,
            'payment_status' => 'pending',
        ]);
        $appointment->services()->attach($service->id, [
            'duration_minutes' => 30,
            'price_cents' => 5000,
            'position' => 1,
        ]);

        $response = $this
            ->actingAs($admin)
            ->withSession(['active_company_id' => $company->id])
            ->post(route('queue.finish', $appointment), [
                'payment_method' => 'pix',
            ]);

        $response->assertRedirect();

        $appointment->refresh();
        $this->assertSame('done', $appointment->status);
        $this->assertSame('paid', $appointment->payment_status);
        $this->assertNotNull($appointment->finished_at);

        $receivable = AccountReceivable::where('appointment_id', $appointment->id)->firstOrFail();
        $this->assertSame('pago', $receivable->status);
        $this->assertSame('pix', $receivable->forma_pagamento);
        $this->assertSame(5000, $receivable->valor_total_cents);

        $entry = CashFlowEntry::where('origem', 'conta_receber')
            ->where('origem_id', $receivable->id)
            ->firstOrFail();
        $this->assertSame('entrada', $entry->tipo);
        $this->assertSame('pix', $entry->forma_pagamento);
        $this->assertSame(5000, $entry->valor_cents);
    }
}
