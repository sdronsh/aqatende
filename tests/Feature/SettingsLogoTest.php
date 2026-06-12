<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SettingsLogoTest extends TestCase
{
    use RefreshDatabase;

    public function test_logo_update_stores_file_for_active_company(): void
    {
        Storage::fake('public');

        $user = User::factory()->create(['is_platform_admin' => true]);
        $company = Company::create(['name' => 'Empresa A']);

        $response = $this
            ->actingAs($user)
            ->withSession(['active_company_id' => $company->id])
            ->put(route('settings.logo.update'), [
                'company_id' => $company->id,
                'logo' => UploadedFile::fake()->image('logo.jpg', 300, 300),
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('settings.logo'));

        $logoPath = CompanySetting::query()
            ->where('company_id', $company->id)
            ->where('key', 'logo_path')
            ->value('value');

        $this->assertNotNull($logoPath);
        Storage::disk('public')->assertExists($logoPath);
    }

    public function test_logo_update_rejects_company_id_different_from_active_session_company(): void
    {
        Storage::fake('public');

        $user = User::factory()->create(['is_platform_admin' => true]);
        $activeCompany = Company::create(['name' => 'Empresa A']);
        $otherCompany = Company::create(['name' => 'Empresa B']);

        $response = $this
            ->actingAs($user)
            ->from(route('settings.logo'))
            ->withSession(['active_company_id' => $activeCompany->id])
            ->put(route('settings.logo.update'), [
                'company_id' => $otherCompany->id,
                'logo' => UploadedFile::fake()->image('logo.jpg', 300, 300),
            ]);

        $response
            ->assertSessionHasErrors('logo')
            ->assertRedirect(route('settings.logo'));

        $this->assertDatabaseMissing('company_settings', [
            'company_id' => $activeCompany->id,
            'key' => 'logo_path',
        ]);

        $this->assertDatabaseMissing('company_settings', [
            'company_id' => $otherCompany->id,
            'key' => 'logo_path',
        ]);
    }

    public function test_logo_update_requires_file_when_not_removing_logo(): void
    {
        Storage::fake('public');

        $user = User::factory()->create(['is_platform_admin' => true]);
        $company = Company::create(['name' => 'Empresa A']);

        $response = $this
            ->actingAs($user)
            ->from(route('settings.logo'))
            ->withSession(['active_company_id' => $company->id])
            ->put(route('settings.logo.update'), [
                'company_id' => $company->id,
            ]);

        $response
            ->assertSessionHasErrors('logo')
            ->assertRedirect(route('settings.logo'));

        $this->assertDatabaseMissing('company_settings', [
            'company_id' => $company->id,
            'key' => 'logo_path',
        ]);
    }
}
