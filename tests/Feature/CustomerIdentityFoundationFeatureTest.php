<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Central\CrmLead;
use App\Models\Central\SuperAdmin;
use App\Models\Central\Tenant;
use App\Services\Platform\AiSales\AiSalesQueryService;
use App\Services\Platform\AiSales\Identity\AiSalesDemoBindingService;
use App\Services\Platform\AiSales\Identity\CustomerIdentity;
use App\Services\Platform\AiSales\Identity\CustomerIdentityResolver;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CustomerIdentityFoundationFeatureTest extends TestCase
{
    private SuperAdmin $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->prepareSqlite();
        Artisan::call('migrate:fresh', ['--database' => 'central', '--force' => true]);
        $this->admin = SuperAdmin::query()->create([
            'name' => 'Sales Admin',
            'email' => 'identity-sales@example.com',
            'password' => Hash::make('password'),
            'status' => 'active',
        ]);
    }

    public function test_same_phone_does_not_create_duplicate_customer(): void
    {
        Sanctum::actingAs($this->admin, ['*']);
        $first = $this->postJson('/api/platform/ai-sales/leads', [
            'name' => 'أحمد',
            'phone' => '01001234567',
            'channel' => 'whatsapp',
        ]);
        $first->assertCreated();
        $id = (int) $first->json('data.id');

        $second = $this->postJson('/api/platform/ai-sales/leads', [
            'name' => 'أحمد',
            'phone' => '+201001234567',
            'channel' => 'whatsapp',
        ]);
        $second->assertSuccessful();
        $this->assertSame($id, (int) $second->json('data.id'));
        $this->assertSame(1, CrmLead::query()->where('source', AiSalesQueryService::SOURCE)->count());
    }

    public function test_existing_customer_receives_new_message_without_duplicate(): void
    {
        Sanctum::actingAs($this->admin, ['*']);
        $leadId = (int) $this->postJson('/api/platform/ai-sales/leads', [
            'name' => 'أحمد محمد',
            'phone' => '201009999111',
            'channel' => 'whatsapp',
        ])->json('data.id');
        $uuid = (string) $this->postJson('/api/platform/ai-sales/conversations', [
            'lead_id' => $leadId,
            'channel' => 'whatsapp',
            'external_id' => '201009999111',
        ])->json('data.id');

        $this->postJson("/api/platform/ai-sales/conversations/{$uuid}/messages", [
            'author' => 'customer',
            'body' => 'السلام عليكم',
        ])->assertOk();
        $again = $this->postJson('/api/platform/ai-sales/conversations', [
            'channel' => 'whatsapp',
            'phone' => '201009999111',
            'external_id' => '201009999111',
        ]);
        $again->assertCreated();
        $this->assertSame($uuid, $again->json('data.id'));
        $this->assertSame($leadId, (int) $again->json('data.lead_id'));
        $this->assertSame(1, CrmLead::query()->where('source', AiSalesQueryService::SOURCE)->count());
    }

    public function test_admin_conversation_list_and_details_show_name_and_phone(): void
    {
        Sanctum::actingAs($this->admin, ['*']);
        $leadId = (int) $this->postJson('/api/platform/ai-sales/leads', [
            'name' => 'Ahmed Mohamed',
            'phone' => '201001112223',
            'channel' => 'whatsapp',
        ])->json('data.id');
        $uuid = (string) $this->postJson('/api/platform/ai-sales/conversations', [
            'lead_id' => $leadId,
            'channel' => 'whatsapp',
            'external_id' => '201001112223',
        ])->json('data.id');
        $this->postJson("/api/platform/ai-sales/conversations/{$uuid}/messages", [
            'author' => 'customer',
            'body' => 'مساء الخير',
        ])->assertOk();

        $list = $this->getJson('/api/platform/ai-sales/conversations');
        $list->assertOk()
            ->assertJsonPath('data.items.0.lead_name', 'Ahmed Mohamed');
        $this->assertStringContainsString('201001112223', (string) $list->json('data.items.0.phone'));

        $details = $this->getJson("/api/platform/ai-sales/conversations/{$uuid}");
        $details->assertOk()
            ->assertJsonPath('data.lead_name', 'Ahmed Mohamed')
            ->assertJsonPath('data.snapshot.name', 'Ahmed Mohamed');
        $this->assertStringContainsString('201001112223', (string) $details->json('data.phone'));
        $this->assertSame('inbound', $details->json('data.messages.0.direction'));
        $this->assertSame('customer', $details->json('data.messages.0.sender'));
        $this->assertSame('whatsapp', $details->json('data.messages.0.channel'));
        $this->assertStringContainsString('201001112223', (string) $details->json('data.messages.0.customer.phone'));
    }

    public function test_unknown_name_falls_back_to_phone_not_unknown_user(): void
    {
        Sanctum::actingAs($this->admin, ['*']);
        $lead = app(CustomerIdentityResolver::class)->resolve([
            'phone' => '201008887776',
            'channel' => 'whatsapp',
        ]);
        $uuid = (string) $this->postJson('/api/platform/ai-sales/conversations', [
            'lead_id' => $lead->id,
            'channel' => 'whatsapp',
            'external_id' => '201008887776',
        ])->json('data.id');
        $details = $this->getJson("/api/platform/ai-sales/conversations/{$uuid}");
        $details->assertOk();
        $this->assertNotSame('Unknown', $details->json('data.lead_name'));
        $this->assertNotSame('Unknown User', $details->json('data.lead_name'));
        $this->assertStringContainsString('201008887776', (string) $details->json('data.lead_name').(string) $details->json('data.phone'));
    }

    public function test_openai_turn_receives_customer_identity_context(): void
    {
        Sanctum::actingAs($this->admin, ['*']);
        $leadId = (int) $this->postJson('/api/platform/ai-sales/leads', [
            'name' => 'أحمد محمد',
            'phone' => '201002223334',
            'business' => 'Atelier Elegance',
            'channel' => 'whatsapp',
        ])->json('data.id');
        $uuid = (string) $this->postJson('/api/platform/ai-sales/conversations', [
            'lead_id' => $leadId,
            'channel' => 'whatsapp',
        ])->json('data.id');

        $sim = $this->postJson('/api/platform/ai-sales/simulate', [
            'conversation_id' => $uuid,
            'message' => 'هاي',
        ]);
        $sim->assertOk();
        $this->assertSame('أحمد محمد', $sim->json('data.customer_identity.customer_name'));
        $this->assertStringContainsString('أحمد محمد', (string) $sim->json('data.customer_context.prompt'));
        $this->assertStringContainsString('Never invent a customer name', (string) $sim->json('data.customer_context.prompt'));
    }

    public function test_existing_demo_account_is_reused(): void
    {
        $tenant = Tenant::query()->create([
            'name' => 'Atelier Elegance',
            'slug' => 'atelier-elegance-demo',
            'database_name' => 'tn_atelier_elegance_demo',
            'status' => 'active',
            'metadata' => [
                'source' => 'demo',
                'admin_email' => 'ahmed.mohamed@demo.dressnmore.com',
                'admin_name' => 'أحمد محمد',
                'phone' => '201003334445',
            ],
        ]);
        $lead = CrmLead::query()->create([
            'name' => 'أحمد محمد',
            'phone' => '201003334445',
            'whatsapp' => '201003334445',
            'atelier_name' => 'Atelier Elegance',
            'source' => AiSalesQueryService::SOURCE,
            'status' => 'trial',
            'tenant_id' => $tenant->id,
            'identity' => [
                'name_source' => CustomerIdentity::SOURCE_EXPLICIT_USER,
                'name_confidence' => CustomerIdentity::CONFIDENCE_HIGH,
            ],
        ]);
        $result = app(AiSalesDemoBindingService::class)->proposeOrReuse($lead, false);
        $this->assertTrue($result['reused']);
        $this->assertFalse($result['created']);
        $this->assertSame($tenant->id, $result['tenant_id']);
        $this->assertSame(1, Tenant::query()->where('metadata->source', 'demo')->count());
    }

    public function test_reset_session_clears_state_and_keeps_identity_history_and_audits(): void
    {
        Sanctum::actingAs($this->admin, ['*']);
        $leadId = (int) $this->postJson('/api/platform/ai-sales/leads', [
            'name' => 'أحمد محمد',
            'phone' => '201004445556',
            'business' => 'أتيليه لورين',
            'channel' => 'whatsapp',
        ])->json('data.id');
        $uuid = (string) $this->postJson('/api/platform/ai-sales/conversations', [
            'lead_id' => $leadId,
            'channel' => 'whatsapp',
            'external_id' => '201004445556',
        ])->json('data.id');

        $this->postJson("/api/platform/ai-sales/conversations/{$uuid}/messages", [
            'author' => 'customer',
            'body' => 'رشحلي أنسب باقة',
        ])->assertOk();

        $conversation = \App\Models\Central\PlatformAiSalesConversation::query()->where('uuid', $uuid)->firstOrFail();
        $lead = \App\Models\Central\CrmLead::query()->findOrFail($leadId);
        $identityBefore = $lead->identity;
        $messageCount = \App\Models\Central\PlatformAiSalesMessage::query()->where('conversation_id', $conversation->id)->count();
        $auditCount = \App\Models\Central\PlatformAiSalesToolAudit::query()->where('conversation_id', $conversation->id)->count();
        $this->assertGreaterThan(0, $messageCount);

        $reset = $this->postJson("/api/platform/ai-sales/conversations/{$uuid}/reset-session");
        $reset->assertOk();
        $this->assertNull($reset->json('data.intent'));
        $this->assertSame('أحمد محمد', $reset->json('data.lead_name'));

        $conversation = $conversation->fresh();
        $this->assertSame('NEW', $conversation->sales_state);
        $this->assertNull($conversation->intent);
        $memory = is_array($conversation->sales_memory) ? $conversation->sales_memory : [];
        $this->assertSame([], $memory['asked_questions'] ?? ['not-cleared']);
        $this->assertNull($memory['intent'] ?? 'x');
        $this->assertNull($memory['pending_question'] ?? null);
        $this->assertNull($memory['pending_slot'] ?? null);
        $this->assertArrayHasKey('session_reset_message_id', $memory);
        $this->assertNull($memory['customer_context'] ?? null);
        $this->assertNotEmpty($memory['session_reset_at'] ?? null);
        $this->assertSame('أحمد محمد', $memory['identity']['customer_name'] ?? null);

        $lead = $lead->fresh();
        $this->assertSame('أحمد محمد', $lead->name);
        $this->assertSame('أتيليه لورين', $lead->atelier_name);
        $this->assertSame('201004445556', (string) $lead->phone);
        $this->assertEquals($identityBefore, $lead->identity);

        $this->assertSame(
            $messageCount,
            \App\Models\Central\PlatformAiSalesMessage::query()->where('conversation_id', $conversation->id)->count()
        );
        $this->assertSame(
            $auditCount,
            \App\Models\Central\PlatformAiSalesToolAudit::query()->where('conversation_id', $conversation->id)->count()
        );
    }

    private function prepareSqlite(): void
    {
        $testingPath = storage_path('framework/testing');
        if (! is_dir($testingPath)) {
            mkdir($testingPath, 0777, true);
        }
        $path = $testingPath.'/central-customer-identity.sqlite';
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
