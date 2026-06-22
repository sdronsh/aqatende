<?php

namespace Tests\Feature\Auth;

use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $company = Company::create([
            'name' => 'Empresa A',
            'cnpj' => '12345678000199',
        ]);
        $user = User::factory()->create(['username' => 'admin']);
        $user->companies()->attach($company);

        $response = $this->post('/login', [
            'company_code' => '12.345.678/0001-99',
            'username' => 'admin',
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $company = Company::create([
            'name' => 'Empresa A',
            'cnpj' => '12345678000199',
        ]);
        $user = User::factory()->create(['username' => 'admin']);
        $user->companies()->attach($company);

        $this->post('/login', [
            'company_code' => '12.345.678/0001-99',
            'username' => 'admin',
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_company_login_warns_when_whatsapp_session_is_not_connected(): void
    {
        Config::set('aqamed.communication.api_url', 'https://communication.test');
        Config::set('aqamed.communication.api_token', 'token');
        Config::set('aqamed.license.api_url', 'https://licenses.test');

        Http::fake([
            'https://licenses.test/api/licenses/lookup*' => Http::response([
                'has_access' => true,
                'status' => 'active',
                'modules' => ['whatsapp'],
            ]),
            'https://communication.test/whatsapp/sessions/session-1/status' => Http::response([
                'uuid' => 'session-1',
                'status' => 'disconnected',
            ]),
        ]);

        [$company, $user] = $this->createCompanyUserWithWhatsappSession();

        $response = $this->post('/login', [
            'company_code' => $company->cnpj,
            'username' => $user->username,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response
            ->assertRedirect(route('dashboard', absolute: false))
            ->assertSessionHas('warning', 'A conexao do WhatsApp da empresa caiu. Acesse Configuracoes > WhatsApp para reconectar.');
    }

    public function test_company_login_does_not_warn_when_whatsapp_session_is_connected(): void
    {
        Config::set('aqamed.communication.api_url', 'https://communication.test');
        Config::set('aqamed.communication.api_token', 'token');
        Config::set('aqamed.license.api_url', 'https://licenses.test');

        Http::fake([
            'https://licenses.test/api/licenses/lookup*' => Http::response([
                'has_access' => true,
                'status' => 'active',
                'modules' => ['whatsapp'],
            ]),
            'https://communication.test/whatsapp/sessions/session-1/status' => Http::response([
                'uuid' => 'session-1',
                'status' => 'connected',
            ]),
        ]);

        [$company, $user] = $this->createCompanyUserWithWhatsappSession();

        $response = $this->post('/login', [
            'company_code' => $company->cnpj,
            'username' => $user->username,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response
            ->assertRedirect(route('dashboard', absolute: false))
            ->assertSessionMissing('warning');
    }

    public function test_company_login_clears_missing_whatsapp_session_and_warns_user(): void
    {
        Config::set('aqamed.communication.api_url', 'https://communication.test');
        Config::set('aqamed.communication.api_token', 'token');
        Config::set('aqamed.license.api_url', 'https://licenses.test');

        Http::fake([
            'https://licenses.test/api/licenses/lookup*' => Http::response([
                'has_access' => true,
                'status' => 'active',
                'modules' => ['whatsapp'],
            ]),
            'https://communication.test/whatsapp/sessions/session-1/status' => Http::response([], 404),
        ]);

        [$company, $user] = $this->createCompanyUserWithWhatsappSession();

        $response = $this->post('/login', [
            'company_code' => $company->cnpj,
            'username' => $user->username,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response
            ->assertRedirect(route('dashboard', absolute: false))
            ->assertSessionHas('warning', 'A sessao WhatsApp salva nao foi encontrada. Acesse Configuracoes > WhatsApp para conectar novamente.');

        $this->assertDatabaseHas('company_settings', [
            'company_id' => $company->id,
            'key' => 'whatsapp_session',
            'value' => null,
        ]);
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create(['is_platform_admin' => true]);

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }

    private function createCompanyUserWithWhatsappSession(): array
    {
        $company = Company::create([
            'name' => 'Empresa A',
            'cnpj' => '12345678000199',
        ]);
        $user = User::factory()->create(['username' => 'admin']);
        $user->companies()->attach($company);

        CompanySetting::create([
            'company_id' => $company->id,
            'key' => 'whatsapp_session',
            'value' => json_encode([
                'uuid' => 'session-1',
                'status' => 'connected',
            ]),
        ]);

        return [$company, $user];
    }
}
