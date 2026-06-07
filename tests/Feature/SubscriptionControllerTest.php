<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Mockery;
use Tests\TestCase;

class SubscriptionControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_subscription_billing_sends_admin_notification_email(): void
    {
        Mail::shouldReceive('raw')
            ->once()
            ->with(
                Mockery::on(fn (string $body) => str_contains($body, 'Nova contratacao AQAtende')
                    && str_contains($body, 'Barbearia Rocha')
                    && str_contains($body, 'https://pagamento.test/fatura/123')),
                Mockery::type('Closure')
            );
        Http::fake([
            'https://licencas.test/api/companies' => Http::response([
                'license_id' => 123,
            ], 201),
            'https://licencas.test/api/subscriptions' => Http::response([
                'payment_url' => 'https://pagamento.test/fatura/123',
            ], 201),
        ]);
        config([
            'aqamed.license.api_url' => 'https://licencas.test',
            'aqamed.license.api_token' => 'token-test',
            'aqamed.license.system_id' => 1,
            'aqamed.subscription.notification_email' => 'admin@aqatende.com.br',
        ]);

        $this->post(route('subscriptions.store', 'essencial'), [
            'name' => 'Barbearia Rocha',
            'business_activity' => 'salao_barbearia',
            'cnpj' => '12345678901',
            'email' => 'contato@barbearia.test',
            'phone' => '(38) 99999-0000',
            'contact_name' => 'Jose Rocha',
            'contact_email' => 'jose@barbearia.test',
            'contact_phone' => '(38) 98888-0000',
            'address_city' => 'Montes Claros',
            'address_state' => 'MG',
        ])->assertRedirect(route('subscriptions.billing', 'essencial'));

        $this->post(route('subscriptions.billing.store', 'essencial'), [
            'due_day' => 10,
            'notes' => 'Assinatura criada via teste.',
        ])->assertRedirect(route('subscriptions.admin', 'essencial'));
    }
}
