<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Central\CrmFollowUp;
use App\Models\Central\CrmLead;
use App\Models\Central\PlatformPermission;
use App\Models\Central\PlatformRole;
use App\Models\Central\SuperAdmin;
use App\Services\Platform\AiSales\AiSalesQueryService;
use App\Support\AiSales\AiSalesHandoffState;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PlatformAiSalesSprint02Test extends TestCase
{
    private string $centralDatabasePath;

    private SuperAdmin $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->prepareSqlite();
        Artisan::call('migrate:fresh', ['--database' => 'central', '--force' => true]);
        $this->admin = SuperAdmin::query()->create([
            'name' => 'Sales Admin',
            'email' => 'ai-sales@example.com',
            'password' => Hash::make('password'),
            'status' => 'active',
        ]);
    }

    public function test_context_exposes_live_catalog_not_stale_prices(): void
    {
        Sanctum::actingAs($this->admin, ['*']);
        $response = $this->getJson('/api/platform/ai-sales/context');
        $response->assertOk()->assertJsonPath('data.pricing_context.never_hardcode', true);
        $this->assertSame('PlanFeatureCatalog+plans', $response->json('data.pricing_context.source'));
        $this->assertSame('starter', $response->json('data.upgrade_mapping.website'));
        $this->assertSame('professional', $response->json('data.upgrade_mapping.smart_consultant'));
        $this->assertSame('business', $response->json('data.upgrade_mapping.factory'));
    }

    public function test_lead_lifecycle_score_and_stage_change(): void
    {
        Sanctum::actingAs($this->admin, ['*']);
        $create = $this->postJson('/api/platform/ai-sales/leads', [
            'name' => 'Mona Atelier',
            'business' => 'Mona',
            'branch_count' => 2,
            'user_count' => 6,
            'channel' => 'website',
            'signals' => ['asked_price' => true, 'asked_demo' => true],
        ]);
        $create->assertCreated()->assertJsonPath('data.source', 'ai_sales');
        $id = (int) $create->json('data.id');
        $this->assertGreaterThan(0, (int) $create->json('data.score'));

        $update = $this->patchJson("/api/platform/ai-sales/leads/{$id}", [
            'stage' => 'qualified',
            'intent' => 'trial_request',
        ]);
        $update->assertOk()->assertJsonPath('data.stage', 'qualified');
        $this->assertDatabaseHas('crm_leads', [
            'id' => $id,
            'status' => 'qualified',
            'intent' => 'trial_request',
            'source' => AiSalesQueryService::SOURCE,
        ], 'central');
    }

    public function test_conversation_messages_and_human_handoff_pauses_ai(): void
    {
        Sanctum::actingAs($this->admin, ['*']);
        $leadId = (int) $this->postJson('/api/platform/ai-sales/leads', [
            'name' => 'Handoff Lead',
            'channel' => 'whatsapp',
        ])->json('data.id');

        $opened = $this->postJson('/api/platform/ai-sales/conversations', [
            'lead_id' => $leadId,
            'channel' => 'whatsapp',
        ]);
        $opened->assertCreated();
        $uuid = (string) $opened->json('data.id');

        $this->postJson("/api/platform/ai-sales/conversations/{$uuid}/messages", [
            'author' => 'customer',
            'body' => 'عايز أجرب النظام',
        ])->assertOk()->assertJsonPath('data.intent', 'trial_request');

        $this->patchJson("/api/platform/ai-sales/conversations/{$uuid}/handoff", [
            'handoff_status' => AiSalesHandoffState::HumanActive->value,
        ])->assertOk()->assertJsonPath('data.handoff_state', 'HUMAN_ACTIVE');

        $this->postJson("/api/platform/ai-sales/conversations/{$uuid}/messages", [
            'author' => 'ai',
            'body' => 'I should not send this',
        ])->assertStatus(422);

        $this->getJson("/api/platform/ai-sales/conversations/{$uuid}")
            ->assertOk()
            ->assertJsonPath('data.lead_id', $leadId);
    }

    public function test_follow_up_create_complete_and_cancel(): void
    {
        Sanctum::actingAs($this->admin, ['*']);
        $leadId = (int) $this->postJson('/api/platform/ai-sales/leads', [
            'name' => 'Follow Lead',
        ])->json('data.id');

        $created = $this->postJson('/api/platform/ai-sales/follow-ups', [
            'lead_id' => $leadId,
            'scheduled_at' => now()->addDay()->toIso8601String(),
            'reason' => 'Check trial interest',
            'channel' => 'website',
        ]);
        $created->assertCreated();
        $id = (int) $created->json('data.id');

        $this->patchJson("/api/platform/ai-sales/follow-ups/{$id}", ['status' => 'completed'])
            ->assertOk()
            ->assertJsonPath('data.status', 'completed');

        $second = (int) $this->postJson('/api/platform/ai-sales/follow-ups', [
            'lead_id' => $leadId,
            'scheduled_at' => now()->addDays(2)->toIso8601String(),
            'reason' => 'Cancel me',
        ])->json('data.id');

        $this->patchJson("/api/platform/ai-sales/follow-ups/{$second}", ['status' => 'cancelled'])
            ->assertOk()
            ->assertJsonPath('data.status', 'cancelled');

        $this->assertInstanceOf(CrmFollowUp::class, CrmFollowUp::query()->find($id));
    }

    public function test_recommend_uses_catalog_not_invented_plan(): void
    {
        Sanctum::actingAs($this->admin, ['*']);
        $this->postJson('/api/platform/ai-sales/recommend', [
            'branch_count' => 2,
            'user_count' => 7,
            'desired_features' => ['website', 'factory'],
        ])->assertOk()->assertJsonPath('data.recommended_plan', 'business');
    }

    public function test_unauthorized_admin_cannot_access_ai_sales(): void
    {
        $role = PlatformRole::query()->create([
            'name' => 'Viewer',
            'slug' => 'viewer-no-ai-sales',
            'is_system' => false,
        ]);
        PlatformPermission::query()->create([
            'key' => 'view_dashboard',
            'name' => 'Dashboard',
            'module' => 'dashboard',
            'sort_order' => 1,
        ]);
        $limited = SuperAdmin::query()->create([
            'name' => 'Limited',
            'email' => 'limited@example.com',
            'password' => Hash::make('password'),
            'status' => 'active',
            'platform_role_id' => $role->id,
        ]);

        Sanctum::actingAs($limited, ['*']);
        $this->getJson('/api/platform/ai-sales/overview')->assertForbidden();
        $this->getJson('/api/platform/ai-sales/leads')->assertForbidden();
        $this->assertNull(CrmLead::query()->where('source', 'ai_sales')->first());
    }

    public function test_knowledge_syncs_catalog_and_keeps_subscription_pricing_separate(): void
    {
        Sanctum::actingAs($this->admin, ['*']);
        $response = $this->getJson('/api/platform/ai-sales/knowledge');
        $response->assertOk();
        $this->assertNotEmpty($response->json('data.items'));
        $this->assertSame(['live_business_data', 'subscription_plan_system', 'approved_knowledge_base', 'ai_reasoning'], $response->json('data.knowledge_priority'));
        $this->assertIsArray($response->json('data.pricing_from_subscription'));
    }

    private function prepareSqlite(): void
    {
        $testingPath = storage_path('framework/testing');
        if (! is_dir($testingPath)) {
            mkdir($testingPath, 0777, true);
        }
        $this->centralDatabasePath = $testingPath.'/central-ai-sales-sprint02.sqlite';
        @unlink($this->centralDatabasePath);
        touch($this->centralDatabasePath);

        Config::set('database.default', 'central');
        Config::set('database.connections.central', [
            'driver' => 'sqlite',
            'database' => $this->centralDatabasePath,
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);
        DB::purge('central');
    }
}
