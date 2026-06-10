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
use Illuminate\Support\Facades\Http;
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

    public function test_agenda_word_starts_whatsapp_booking_flow(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-20 10:00:00', 'America/Sao_Paulo'));
        $context = $this->createWhatsappBookingContext();

        $sentMessages = [];
        $this->fakeCommunicationClient($sentMessages);

        $this->postJson('/api/whatsapp/webhook', $this->webhookPayload($context, 'agenda'))
            ->assertOk()
            ->assertJson(['ok' => true]);

        $this->assertStringContainsString('Escolha o servico', $sentMessages[0] ?? '');
        $this->assertDatabaseHas('company_settings', [
            'company_id' => $context['company']->id,
            'key' => 'whatsapp_flow_state_5599999999999',
        ]);
    }

    public function test_olx_command_returns_vehicle_fipe_analysis_without_starting_booking_flow(): void
    {
        config(['aqamed.vehicle_lookup.enabled' => true]);
        $context = $this->createWhatsappBookingContext();

        Http::fake([
            'www.olx.com.br/*' => Http::response('<html><head><meta property="og:title" content="Honda Civic 2015"><meta property="og:description" content="Honda Civic 2015 com 80.000 km"><meta property="product:price:amount" content="55000.00"></head></html>'),
            'parallelum.com.br/fipe/api/v1/carros/marcas' => Http::response([
                ['codigo' => '25', 'nome' => 'Honda'],
            ]),
            'parallelum.com.br/fipe/api/v1/carros/marcas/25/modelos' => Http::response([
                'modelos' => [
                    ['codigo' => '1234', 'nome' => 'Civic'],
                ],
            ]),
            'parallelum.com.br/fipe/api/v1/carros/marcas/25/modelos/1234/anos' => Http::response([
                ['codigo' => '2015-1', 'nome' => '2015 Gasolina'],
            ]),
            'parallelum.com.br/fipe/api/v1/carros/marcas/25/modelos/1234/anos/2015-1' => Http::response([
                'Valor' => 'R$ 50.000,00',
                'Marca' => 'Honda',
                'Modelo' => 'Civic',
                'AnoModelo' => 2015,
                'Combustivel' => 'Gasolina',
                'CodigoFipe' => '014000-0',
                'MesReferencia' => 'junho de 2026',
            ]),
        ]);

        $sentMessages = [];
        $this->fakeCommunicationClient($sentMessages);

        $this->postJson('/api/whatsapp/webhook', $this->webhookPayload($context, 'olx:https://www.olx.com.br/autos-e-pecas/carros-vans-e-utilitarios/honda-civic-2015'))
            ->assertOk()
            ->assertJson(['ok' => true, 'vehicle_lookup' => true]);

        $this->assertStringContainsString('Analise OLX x FIPE', $sentMessages[0] ?? '');
        $this->assertStringContainsString('Valor anunciado: R$ 55.000,00', $sentMessages[0] ?? '');
        $this->assertStringContainsString('FIPE: R$ 50.000,00', $sentMessages[0] ?? '');
        $this->assertStringContainsString('acima da FIPE', $sentMessages[0] ?? '');
        $this->assertDatabaseMissing('company_settings', [
            'company_id' => $context['company']->id,
            'key' => 'whatsapp_flow_state_5599999999999',
        ]);
    }

    public function test_olx_command_falls_back_to_url_slug_when_olx_blocks_html_fetch(): void
    {
        config(['aqamed.vehicle_lookup.enabled' => true]);
        $context = $this->createWhatsappBookingContext();

        Http::fake([
            'mg.olx.com.br/*' => Http::response('', 403),
            'parallelum.com.br/fipe/api/v1/carros/marcas' => Http::response([
                ['codigo' => '31', 'nome' => 'Kia'],
            ]),
            'parallelum.com.br/fipe/api/v1/carros/marcas/31/modelos' => Http::response([
                'modelos' => [
                    ['codigo' => '4321', 'nome' => 'Cerato'],
                ],
            ]),
            'parallelum.com.br/fipe/api/v1/carros/marcas/31/modelos/4321/anos' => Http::response([
                ['codigo' => '2014-1', 'nome' => '2014 Gasolina'],
            ]),
            'parallelum.com.br/fipe/api/v1/carros/marcas/31/modelos/4321/anos/2014-1' => Http::response([
                'Valor' => 'R$ 52.000,00',
                'Marca' => 'Kia',
                'Modelo' => 'Cerato 1.6 16V Flex Aut.',
                'AnoModelo' => 2014,
                'Combustivel' => 'Gasolina',
                'CodigoFipe' => '018097-5',
                'MesReferencia' => 'junho de 2026',
            ]),
        ]);

        $sentMessages = [];
        $this->fakeCommunicationClient($sentMessages);

        $this->postJson('/api/whatsapp/webhook', $this->webhookPayload($context, 'olx:https://mg.olx.com.br/regiao-de-governador-valadares-e-teofilo-otoni/autos-e-pecas/carros-vans-e-utilitarios/kia-motors-cerato-1-6-16v-flex-aut-2014-1507185272?lis=listing_2020'))
            ->assertOk()
            ->assertJson(['ok' => true, 'vehicle_lookup' => true]);

        $this->assertStringContainsString('Anuncio: Kia Motors Cerato 1 6 16V Flex Aut 2014', $sentMessages[0] ?? '');
        $this->assertStringContainsString('Valor anunciado: nao consegui ler automaticamente no anuncio.', $sentMessages[0] ?? '');
        $this->assertStringContainsString('FIPE: R$ 52.000,00', $sentMessages[0] ?? '');
        $this->assertStringContainsString('Modelo FIPE: Kia Cerato 1.6 16V Flex Aut.', $sentMessages[0] ?? '');
    }

    public function test_confirmed_open_appointment_shows_cancel_menu_on_whatsapp(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-20 10:00:00', 'America/Sao_Paulo'));
        $context = $this->createWhatsappBookingContext();
        $scheduledAt = Carbon::parse('2026-05-21 09:00:00', 'America/Sao_Paulo');

        Appointment::create([
            'clinic_id' => $context['clinic']->id,
            'unit_id' => $context['unit']->id,
            'professional_id' => $context['professional']->id,
            'patient_id' => $context['patient']->id,
            'service_id' => $context['service']->id,
            'status' => 'confirmado',
            'channel' => 'whatsapp',
            'scheduled_at' => $scheduledAt,
            'ends_at' => $scheduledAt->copy()->addMinutes(30),
            'duration_minutes' => 30,
            'payment_status' => 'pending',
        ]);

        $sentMessages = [];
        $this->fakeCommunicationClient($sentMessages);

        $this->postJson('/api/whatsapp/webhook', $this->webhookPayload($context, 'oi'))
            ->assertOk()
            ->assertJson(['ok' => true]);

        $this->assertStringContainsString('Encontrei um agendamento em aberto', $sentMessages[0] ?? '');
        $this->assertStringContainsString('1 - Cancelar este agendamento', $sentMessages[0] ?? '');
    }

    public function test_open_appointment_menu_is_shown_even_when_previous_flow_state_is_stale(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-20 10:00:00', 'America/Sao_Paulo'));
        $context = $this->createWhatsappBookingContext();
        $scheduledAt = Carbon::parse('2026-05-21 09:00:00', 'America/Sao_Paulo');

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
            'step' => 'service',
            'services' => [$context['service']->id],
        ]);

        $sentMessages = [];
        $this->fakeCommunicationClient($sentMessages);

        $this->postJson('/api/whatsapp/webhook', $this->webhookPayload($context, 'oi'))
            ->assertOk()
            ->assertJson(['ok' => true]);

        $this->assertStringContainsString('Encontrei um agendamento em aberto', $sentMessages[0] ?? '');
        $this->assertStringContainsString('1 - Cancelar este agendamento', $sentMessages[0] ?? '');
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
