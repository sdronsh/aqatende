<?php

namespace Tests\Feature;

use App\Models\Clinic;
use App\Models\Company;
use App\Models\Professional;
use App\Models\ScheduleBlock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ScheduleBlockWebControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_global_professional_schedule_block(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-05 10:00:00'));
        [$company, $professional, $admin] = $this->createContext();

        $response = $this
            ->actingAs($admin)
            ->withSession(['active_company_id' => $company->id])
            ->post(route('schedule-blocks.store'), [
                'professional_id' => $professional->id,
                'date' => '2026-06-08',
                'all_day' => '1',
                'start_time' => '08:00',
                'end_time' => '18:00',
                'reason' => 'Folga',
            ]);

        $response->assertRedirect(route('schedule-blocks.index'));
        $response->assertSessionHasNoErrors();

        $block = ScheduleBlock::firstOrFail();
        $this->assertSame($professional->id, $block->professional_id);
        $this->assertNull($block->unit_id);
        $this->assertSame('2026-06-08 00:00:00', $block->starts_at->format('Y-m-d H:i:s'));
        $this->assertSame('Folga', $block->reason);
    }

    public function test_user_can_remove_schedule_block(): void
    {
        [$company, $professional, $admin] = $this->createContext();
        $block = ScheduleBlock::create([
            'professional_id' => $professional->id,
            'unit_id' => null,
            'starts_at' => Carbon::parse('2026-06-08 08:00:00'),
            'ends_at' => Carbon::parse('2026-06-08 18:00:00'),
            'reason' => 'Treinamento',
        ]);

        $response = $this
            ->actingAs($admin)
            ->withSession(['active_company_id' => $company->id])
            ->delete(route('schedule-blocks.destroy', $block));

        $response->assertRedirect(route('schedule-blocks.index'));
        $this->assertDatabaseMissing('schedule_blocks', ['id' => $block->id]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function createContext(): array
    {
        $company = Company::create(['name' => 'Empresa Teste']);
        Clinic::create([
            'company_id' => $company->id,
            'name' => 'Unidade Principal',
            'terms_version' => config('terms.usage.version'),
            'terms_accepted_at' => now(),
        ]);
        $professional = Professional::create([
            'company_id' => $company->id,
            'display_name' => 'Profissional Teste',
            'active' => true,
        ]);
        $admin = User::factory()->create(['is_platform_admin' => true]);

        return [$company, $professional, $admin];
    }
}
