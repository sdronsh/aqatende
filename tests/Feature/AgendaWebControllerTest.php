<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Clinic;
use App\Models\Company;
use App\Models\Patient;
use App\Models\Professional;
use App\Models\ScheduleBlock;
use App\Models\Service;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AgendaWebControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_cancelled_appointments_are_hidden_from_agenda_view(): void
    {
        $company = Company::create(['name' => 'Empresa Teste']);
        $clinic = Clinic::create([
            'company_id' => $company->id,
            'name' => 'Unidade Principal',
            'terms_version' => config('terms.usage.version'),
            'terms_accepted_at' => now(),
        ]);
        $unit = Unit::create([
            'clinic_id' => $clinic->id,
            'name' => 'Unidade Central',
            'address_line1' => 'Rua Teste',
            'city' => 'Sao Paulo',
            'state' => 'SP',
            'zip' => '01000-000',
            'active' => true,
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
        $activePatient = Patient::create(['full_name' => 'Cliente Ativo']);
        $activePatient->companies()->attach($company);
        $cancelledPatient = Patient::create(['full_name' => 'Cliente Cancelado']);
        $cancelledPatient->companies()->attach($company);
        $admin = User::factory()->create(['is_platform_admin' => true]);
        $date = Carbon::parse('2026-06-02 09:00:00');

        $activeAppointment = Appointment::create([
            'clinic_id' => $clinic->id,
            'unit_id' => $unit->id,
            'patient_id' => $activePatient->id,
            'service_id' => $service->id,
            'status' => 'agendado',
            'channel' => 'manual',
            'scheduled_at' => $date,
            'ends_at' => $date->copy()->addMinutes(30),
            'duration_minutes' => 30,
        ]);
        $cancelledAppointment = Appointment::create([
            'clinic_id' => $clinic->id,
            'unit_id' => $unit->id,
            'patient_id' => $cancelledPatient->id,
            'service_id' => $service->id,
            'status' => 'cancelado',
            'channel' => 'manual',
            'scheduled_at' => $date->copy()->addHour(),
            'ends_at' => $date->copy()->addHour()->addMinutes(30),
            'duration_minutes' => 30,
        ]);

        $response = $this
            ->actingAs($admin)
            ->withSession(['active_company_id' => $company->id])
            ->get(route('agenda.index', [
                'view' => 'day',
                'date' => $date->toDateString(),
                'unit_id' => $unit->id,
            ]));

        $response->assertOk();
        $appointmentIds = $response->viewData('appointments')->pluck('id')->all();

        $this->assertContains($activeAppointment->id, $appointmentIds);
        $this->assertNotContains($cancelledAppointment->id, $appointmentIds);
    }

    public function test_global_schedule_blocks_are_visible_when_unit_is_filtered(): void
    {
        $company = Company::create(['name' => 'Empresa Teste']);
        $clinic = Clinic::create([
            'company_id' => $company->id,
            'name' => 'Unidade Principal',
            'terms_version' => config('terms.usage.version'),
            'terms_accepted_at' => now(),
        ]);
        $unit = Unit::create([
            'clinic_id' => $clinic->id,
            'name' => 'Unidade Central',
            'address_line1' => 'Rua Teste',
            'city' => 'Sao Paulo',
            'state' => 'SP',
            'zip' => '01000-000',
            'active' => true,
        ]);
        $professional = Professional::create([
            'company_id' => $company->id,
            'display_name' => 'Profissional Teste',
            'active' => true,
        ]);
        $admin = User::factory()->create(['is_platform_admin' => true]);
        $block = ScheduleBlock::create([
            'professional_id' => $professional->id,
            'unit_id' => null,
            'starts_at' => Carbon::parse('2026-06-02 09:00:00'),
            'ends_at' => Carbon::parse('2026-06-02 18:00:00'),
            'reason' => 'Folga',
        ]);

        $response = $this
            ->actingAs($admin)
            ->withSession(['active_company_id' => $company->id])
            ->get(route('agenda.index', [
                'view' => 'day',
                'date' => '2026-06-02',
                'unit_id' => $unit->id,
            ]));

        $response->assertOk();

        $events = collect($response->viewData('eventsByProfessional')[$professional->id] ?? []);

        $this->assertTrue($events->contains(fn (array $event) => $event['type'] === 'block' && $event['title'] === $block->reason));
    }
}
