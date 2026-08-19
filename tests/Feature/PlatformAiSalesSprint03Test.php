<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Central\SuperAdmin;
use App\Services\Platform\AiSales\AiSalesQueryService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PlatformAiSalesSprint03Test extends TestCase
{
    private SuperAdmin $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->prepareSqlite();
        Artisan::call('migrate:fresh', ['--database' => 'central', '--force' => true]);
        $this->admin = SuperAdmin::query()->create([
            'name' => 'Sales Admin',
            'email' => 'ai-sales-s03@example.com',
            'password' => Hash::make('password'),
            'status' => 'active',
        ]);
    }

    public function test_simulate_endpoint_returns_sales_contract(): void
    {
        Sanctum::actingAs($this->admin, ['*']);
        $response = $this->postJson('/api/platform/ai-sales/simulate', [
            'message' => 'عندي فرعين و7 موظفين ومحتاج موقع.',
        ]);
        $response->assertOk()
            ->assertJsonPath('data.recommended_next_action', 'recommend');
        $this->assertSame('professional', $response->json('data.lead_updates.interested_plan'));
        $this->assertArrayHasKey('response', $response->json('data'));
        $this->assertArrayHasKey('confidence', $response->json('data'));
        $this->assertArrayHasKey('tool_actions', $response->json('data'));
    }

    public function test_persisted_turn_extracts_profile_and_audits_tools(): void
    {
        Sanctum::actingAs($this->admin, ['*']);
        $leadId = (int) $this->postJson('/api/platform/ai-sales/leads', [
            'name' => 'Sprint03 Lead',
            'channel' => 'website',
        ])->json('data.id');
        $uuid = (string) $this->postJson('/api/platform/ai-sales/conversations', [
            'lead_id' => $leadId,
            'channel' => 'website',
        ])->json('data.id');

        $this->postJson("/api/platform/ai-sales/conversations/{$uuid}/messages", [
            'author' => 'customer',
            'body' => 'عندي فرعين و6 موظفين وبنعمل 100 فاتورة وبستخدم Excel.',
        ])->assertOk();

        $this->assertDatabaseHas('crm_leads', [
            'id' => $leadId,
            'source' => AiSalesQueryService::SOURCE,
            'branches_count' => 2,
            'employees_count' => 6,
        ], 'central');
    }

    private function prepareSqlite(): void
    {
        $testingPath = storage_path('framework/testing');
        if (! is_dir($testingPath)) {
            mkdir($testingPath, 0777, true);
        }
        $path = $testingPath.'/central-ai-sales-sprint03.sqlite';
        @unlink($path);
        touch($path);
        Config::set('database.default', 'central');
        Config::set('database.connections.central', [
            'driver' => 'sqlite',
            'database' => $path,
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);
        DB::purge('central');
    }
}
