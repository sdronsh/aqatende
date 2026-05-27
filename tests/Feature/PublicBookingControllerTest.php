<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Clinic;
use App\Models\Company;
use App\Models\Patient;
use App\Models\PatientBookingLink;
use App\Models\Professional;
use App\Models\Service;
use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\TestCase;

class PublicBookingControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_patient_can_confirm_second_public_booking_in_sequence_without_picking_time(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-20 10:00:00', 'America/Sao_Paulo'));
        $context = $this->createPublicBookingContext();

        $firstLink = PatientBookingLink::create([
            'company_id' => $context['company']->id,
            'patient_id' => $context['patient']->id,
            'token' => Str::random(48),
            'expires_at' => now()->addDays(7),
        ]);
        $firstStart = Carbon::parse('2026-05-21 09:00:00', 'America/Sao_Paulo');

        $this->post(route('public.booking.store', $firstLink->token), [
            'service_id' => $context['firstService']->id,
            'unit_id' => $context['unit']->id,
            'slot' => $context['professional']->id.'|'.$firstStart->toDateTimeString(),
            'booking_action' => 'finish',
        ])->assertOk()
            ->assertSee('Agendamento confirmado')
            ->assertSee('Incluir novo agendamento');

        $secondLink = PatientBookingLink::query()
            ->where('id', '!=', $firstLink->id)
            ->latest('id')
            ->firstOrFail();

        $secondShow = $this->get(route('public.booking.show', [
            'token' => $secondLink->token,
            'service_id' => $context['secondService']->id,
        ]));
        $secondShow->assertOk()
            ->assertSee('Escolha o proximo servico')
            ->assertSee('Horario em sequencia')
            ->assertSee('10:00');
        preg_match('/name="slot" value="([^"]+)"/', $secondShow->getContent(), $matches);
        $this->assertNotEmpty($matches[1] ?? null);
        $this->assertSame($context['professional']->id.'|'.$firstStart->copy()->addMinutes(60)->toDateTimeString(), $matches[1]);

        $secondStore = $this->post(route('public.booking.store', $secondLink->token), [
            'service_id' => $context['secondService']->id,
            'unit_id' => $context['unit']->id,
            'slot' => $matches[1],
            'booking_action' => 'finish',
        ]);
        $secondStore->assertSessionHasNoErrors();
        $secondStore->assertOk()
            ->assertSee('Agendamento confirmado')
            ->assertSee('Bronze')
            ->assertSee('Manicure')
            ->assertSee('09:00')
            ->assertSee('10:00');

        $this->assertSame(2, Appointment::count());
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function createPublicBookingContext(): array
    {
        $company = Company::create(['name' => 'Empresa Teste']);
        $clinic = Clinic::create([
            'company_id' => $company->id,
            'name' => 'Unidade Principal',
            'terms_version' => '1.0',
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
        $firstService = Service::create([
            'clinic_id' => $clinic->id,
            'unit_id' => $unit->id,
            'name' => 'Bronze',
            'duration_minutes' => 60,
            'modality' => 'presencial',
            'price_cents' => 15000,
            'active' => true,
        ]);
        $secondService = Service::create([
            'clinic_id' => $clinic->id,
            'unit_id' => $unit->id,
            'name' => 'Manicure',
            'duration_minutes' => 30,
            'modality' => 'presencial',
            'price_cents' => 5000,
            'active' => true,
        ]);
        $professional = Professional::create([
            'company_id' => $company->id,
            'display_name' => 'Profissional Teste',
            'active' => true,
        ]);
        $professional->units()->attach($unit);
        $professional->services()->attach($firstService, ['active' => true]);
        $professional->services()->attach($secondService, ['active' => true]);

        $patient = Patient::create([
            'full_name' => 'Cliente Teste',
            'cellphone' => '(99) 99999-9999',
            'phone' => '(99) 99999-9999',
        ]);
        $patient->companies()->attach($company);

        return compact('company', 'clinic', 'unit', 'firstService', 'secondService', 'professional', 'patient');
    }
}
