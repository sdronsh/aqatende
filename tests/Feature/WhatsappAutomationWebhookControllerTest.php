<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Clinic;
use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\Patient;
use App\Models\PatientBookingLink;
use App\Models\Professional;
use App\Models\Service;
use App\Models\Unit;
use App\Services\Communication\CommunicationClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class WhatsappAutomationWebhookControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_occupied_whatsapp_time_requests_another_time_without_creating_appointment(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-01 10:00:00', 'America/Sao_Paulo'));
        $context = $this->createWhatsappBookingContext();
        $scheduledAt = Carbon::parse('2026-05-15 17:00:00', 'America/Sao_Paulo');

        Appointment::create([
            'clinic_id' => $context['clinic']->id,
            'unit_id' => $context['unit']->id,
            'professional_id' => $context['professional']->id,
            'patient_id' => $context['patient']->id,
            'service_id' => $context['service']->id,
            'status' => 'agendado',
            'channel' => 'whatsapp',
            'scheduled_at' => $scheduledAt,
            'ends_at' => $scheduledAt->copy()->addMinutes(30),
            'duration_minutes' => 30,
            'payment_status' => 'pending',
        ]);

        $this->setWhatsappState($context, [
            'step' => 'datetime',
            'service_id' => $context['service']->id,
            'professional_id' => $context['professional']->id,
        ]);

        $sentMessages = [];
        $this->fakeCommunicationClient($sentMessages);

        $this->postJson('/api/whatsapp/webhook', $this->webhookPayload($context, '15/05 17:00'))
            ->assertOk()
            ->assertJson(['ok' => true]);

        $this->assertSame(1, Appointment::count());
        $this->assertStringContainsString('horario ja esta ocupado', $sentMessages[0] ?? '');
    }

    public function test_past_date_is_not_scheduled_for_next_year_outside_booking_window(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-20 10:00:00', 'America/Sao_Paulo'));
        $context = $this->createWhatsappBookingContext();

        $this->setWhatsappState($context, [
            'step' => 'datetime',
            'service_id' => $context['service']->id,
            'professional_id' => $context['professional']->id,
        ]);

        $sentMessages = [];
        $this->fakeCommunicationClient($sentMessages);

        $this->postJson('/api/whatsapp/webhook', $this->webhookPayload($context, '15/05 17:00'))
            ->assertOk()
            ->assertJson(['ok' => true]);

        $this->assertSame(0, Appointment::count());
        $this->assertStringContainsString('ja passou', $sentMessages[0] ?? '');
        $this->assertDatabaseMissing('appointments', [
            'scheduled_at' => '2027-05-15 17:00:00',
        ]);
    }

    public function test_existing_patient_receives_public_booking_link_from_available_command(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-20 10:00:00', 'America/Sao_Paulo'));
        $context = $this->createWhatsappBookingContext();

        $sentMessages = [];
        $this->fakeCommunicationClient($sentMessages);

        $this->postJson('/api/whatsapp/webhook', $this->webhookPayload($context, 'disponivel'))
            ->assertOk()
            ->assertJson(['ok' => true]);

        $bookingLink = PatientBookingLink::firstOrFail();

        $this->assertSame($context['company']->id, $bookingLink->company_id);
        $this->assertSame($context['patient']->id, $bookingLink->patient_id);
        $this->assertStringContainsString(route('public.booking.show', $bookingLink->token), $sentMessages[0] ?? '');
        $this->assertDatabaseHas('company_settings', [
            'company_id' => $context['company']->id,
            'key' => 'whatsapp_flow_state_5599999999999',
            'value' => json_encode(['step' => 'start']),
        ]);
    }

    public function test_unknown_patient_informs_name_before_receiving_public_booking_link(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-20 10:00:00', 'America/Sao_Paulo'));
        $context = $this->createWhatsappBookingContext();
        $context['patient']->delete();

        $sentMessages = [];
        $this->fakeCommunicationClient($sentMessages);

        $this->postJson('/api/whatsapp/webhook', $this->webhookPayload($context, 'disponivel'))
            ->assertOk()
            ->assertJson(['ok' => true]);

        $this->assertSame(0, PatientBookingLink::count());
        $this->assertStringContainsString('informe seu nome completo', $sentMessages[0] ?? '');

        $this->postJson('/api/whatsapp/webhook', $this->webhookPayload($context, 'Cliente Novo'))
            ->assertOk()
            ->assertJson(['ok' => true]);

        $patient = Patient::where('full_name', 'Cliente Novo')->firstOrFail();
        $bookingLink = PatientBookingLink::firstOrFail();

        $this->assertSame($patient->id, $bookingLink->patient_id);
        $this->assertTrue($patient->companies()->whereKey($context['company']->id)->exists());
        $this->assertStringContainsString(route('public.booking.show', $bookingLink->token), $sentMessages[1] ?? '');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function createWhatsappBookingContext(): array
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
            'whatsapp_booking_enabled' => true,
        ]);
        $professional = Professional::create([
            'company_id' => $company->id,
            'display_name' => 'Profissional Teste',
            'active' => true,
        ]);
        $professional->units()->attach($unit);
        $professional->services()->attach($service, ['active' => true]);

        $patient = Patient::create([
            'full_name' => 'Cliente Teste',
            'cellphone' => '(99) 99999-9999',
            'phone' => '(99) 99999-9999',
        ]);
        $patient->companies()->attach($company);

        CompanySetting::create([
            'company_id' => $company->id,
            'key' => 'whatsapp_session',
            'value' => json_encode(['uuid' => 'session-test']),
        ]);
        CompanySetting::create([
            'company_id' => $company->id,
            'key' => 'whatsapp_automation',
            'value' => json_encode([
                'flow' => [
                    'bot_enabled' => true,
                    'booking_window_months' => 3,
                ],
            ]),
        ]);

        return compact('company', 'clinic', 'unit', 'service', 'professional', 'patient');
    }

    private function setWhatsappState(array $context, array $state): void
    {
        CompanySetting::create([
            'company_id' => $context['company']->id,
            'key' => 'whatsapp_flow_state_5599999999999',
            'value' => json_encode($state),
        ]);
    }

    private function webhookPayload(array $context, string $text): array
    {
        return [
            'session_uuid' => 'session-test',
            'phone_number' => '5599999999999',
            'text' => $text,
        ];
    }

    private function fakeCommunicationClient(array &$sentMessages): void
    {
        $this->app->instance(CommunicationClient::class, new class($sentMessages) extends CommunicationClient {
            public function __construct(private array &$sentMessages)
            {
            }

            public function sendWhatsappMessage(string $uuid, string $phone, string $text): array
            {
                $this->sentMessages[] = $text;

                return ['ok' => true];
            }
        });
    }
}
