<?php

declare(strict_types=1);

namespace DressnMore\Aos\TenantAi\Tests\Unit;

use DressnMore\Aos\TenantAi\Application\AiSessionManager;
use DressnMore\Aos\TenantAi\Application\AiWorkspaceManager;
use DressnMore\Aos\TenantAi\Application\ConversationManager;
use DressnMore\Aos\TenantAi\Application\ConversationMemoryService;
use DressnMore\Aos\TenantAi\Application\MessageManager;
use DressnMore\Aos\TenantAi\Application\PermissionResolver;
use DressnMore\Aos\TenantAi\Application\SubscriptionResolver;
use DressnMore\Aos\TenantAi\Application\TenantContextBuilder;
use DressnMore\Aos\TenantAi\Application\TenantIntegrationRegistry;
use DressnMore\Aos\TenantAi\Application\ToolAccessGuard;
use DressnMore\Aos\TenantAi\Domain\Conversation\AiConversation;
use DressnMore\Aos\TenantAi\Domain\Conversation\ConversationStatus;
use DressnMore\Aos\TenantAi\Domain\Dashboard\AiDashboardMenu;
use DressnMore\Aos\TenantAi\Domain\Integration\IntegrationChannel;
use DressnMore\Aos\TenantAi\Domain\Integration\TenantIntegrationBinding;
use DressnMore\Aos\TenantAi\Domain\Message\MessageRole;
use DressnMore\Aos\TenantAi\Domain\Policies\TenantAiPolicies;
use DressnMore\Aos\TenantAi\Domain\Session\AiSession;
use DressnMore\Aos\TenantAi\Domain\Subscription\SubscriptionPlan;
use DressnMore\Aos\TenantAi\Infrastructure\InMemory\InMemoryConversationProvider;
use DressnMore\Aos\TenantAi\Infrastructure\InMemory\InMemoryIntegrationProvider;
use DressnMore\Aos\TenantAi\Infrastructure\InMemory\InMemoryMemoryProvider;
use DressnMore\Aos\TenantAi\Infrastructure\InMemory\InMemoryMessageProvider;
use DressnMore\Aos\TenantAi\Infrastructure\InMemory\InMemorySessionProvider;
use DressnMore\Aos\TenantAi\Infrastructure\InMemory\InMemoryTenantAiEventPublisher;
use DressnMore\Aos\TenantAi\Infrastructure\InMemory\InMemoryWorkspaceProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class TenantAiPlatformTest extends TestCase
{
    private InMemoryWorkspaceProvider $workspaces;
    private AiWorkspaceManager $workspaceManager;
    private ConversationManager $conversations;
    private MessageManager $messages;
    private PermissionResolver $permissions;
    private SubscriptionResolver $subscriptions;
    private ToolAccessGuard $guard;
    private TenantIntegrationRegistry $integrations;
    private TenantAiPolicies $policies;
    private InMemoryTenantAiEventPublisher $events;

    protected function setUp(): void
    {
        $this->events = new InMemoryTenantAiEventPublisher();
        $this->workspaces = new InMemoryWorkspaceProvider();
        $this->policies = new TenantAiPolicies();
        $this->workspaceManager = new AiWorkspaceManager($this->workspaces, $this->policies, $this->events);
        $conversationProvider = new InMemoryConversationProvider();
        $this->conversations = new ConversationManager($conversationProvider, $this->policies, $this->events);
        $this->messages = new MessageManager(new InMemoryMessageProvider(), $this->conversations, $this->events);
        $this->permissions = new PermissionResolver($this->policies);
        $this->subscriptions = new SubscriptionResolver($this->policies);
        $this->guard = new ToolAccessGuard($this->policies);
        $this->integrations = new TenantIntegrationRegistry(new InMemoryIntegrationProvider(), $this->policies);
    }

    public function test_workspace_created_per_tenant(): void
    {
        $ws = $this->workspaceManager->ensureForTenant('tenant_a');
        $this->assertSame('tenant_a', $ws->tenantId());
        $this->assertTrue($ws->aiEnabled());
        $again = $this->workspaceManager->ensureForTenant('tenant_a');
        $this->assertSame($ws->workspaceId(), $again->workspaceId());
    }

    public function test_tenant_not_found(): void
    {
        $this->expectException(RuntimeException::class);
        $this->workspaceManager->ensureForTenant('');
    }

    public function test_workspace_not_found(): void
    {
        $this->expectException(RuntimeException::class);
        $this->workspaceManager->getOrFail('missing_tenant');
    }

    public function test_multi_conversation_and_messages(): void
    {
        $ws = $this->workspaceManager->ensureForTenant('tenant_a');
        $c1 = $this->conversations->start('tenant_a', $ws->workspaceId(), 'حجز فستان');
        $c2 = $this->conversations->start('tenant_a', $ws->workspaceId(), 'عميل جديد');
        $this->messages->append('tenant_a', $c1->conversationId(), MessageRole::User, 'أريد حجز');
        $this->messages->append('tenant_a', $c1->conversationId(), MessageRole::Assistant, 'تمام', 12);
        $history = $this->messages->history('tenant_a', $c1->conversationId());
        $this->assertCount(2, $history);
        $this->assertCount(2, $this->conversations->list('tenant_a'));
        $this->assertNotEmpty($this->conversations->search('tenant_a', 'حجز'));
        $this->conversations->rename('tenant_a', $c2->conversationId(), 'إنشاء عميل');
        $this->conversations->close('tenant_a', $c1->conversationId());
        $this->conversations->archive('tenant_a', $c2->conversationId());
        $this->assertSame(ConversationStatus::Archived, $this->conversations->getOrFail('tenant_a', $c2->conversationId())->status());
    }

    public function test_conversation_not_found(): void
    {
        $this->expectException(RuntimeException::class);
        $this->conversations->getOrFail('tenant_a', 'missing');
    }

    public function test_context_builder_metadata_only(): void
    {
        $this->workspaceManager->ensureForTenant('tenant_a');
        $builder = new TenantContextBuilder($this->workspaces);
        $session = new AiSession('tenant_a', 'ws_tenant_a', userId: 'u1', branchId: 'b1');
        $ctx = $builder->build($session, 'manager', ['dresses.view'], 'SA');
        $this->assertSame('tenant_a', $ctx->tenantId());
        $this->assertSame('ar', $ctx->language());
        $this->assertSame('SA', $ctx->country());
        $this->assertArrayHasKey('permissions', $ctx->toArray());
    }

    public function test_user_without_permission(): void
    {
        $resolution = $this->permissions->resolve('u1', 'staff', ['dresses.view'], ['Customer.Read'], ['GetCustomer']);
        $this->expectException(RuntimeException::class);
        $this->permissions->assertToolAllowed($resolution, 'CreateReservation');
    }

    public function test_tool_not_in_subscription_plan(): void
    {
        $entitlement = $this->subscriptions->resolve(SubscriptionPlan::Basic);
        $this->expectException(RuntimeException::class);
        $this->subscriptions->assertToolAllowed($entitlement, 'GenerateReport');
    }

    public function test_cross_tenant_access_denied(): void
    {
        $this->expectException(RuntimeException::class);
        $this->policies->assertTenantIsolation('tenant_a', 'tenant_b');
    }

    public function test_conversation_does_not_belong_to_tenant(): void
    {
        $foreign = new AiConversation('c1', 'tenant_b', 'ws_b', 'x');
        $this->expectException(RuntimeException::class);
        $this->policies->assertConversationIsolation('tenant_a', $foreign);
    }

    public function test_integration_not_enabled(): void
    {
        $this->integrations->register(new TenantIntegrationBinding('i1', 'tenant_a', IntegrationChannel::WhatsApp, false));
        $this->expectException(RuntimeException::class);
        $this->integrations->assertEnabled('tenant_a', IntegrationChannel::WhatsApp);
    }

    public function test_tool_access_guard_and_dashboard(): void
    {
        $perms = $this->permissions->resolve('u1', 'admin', ['*'], ['Customer.Read'], ['GetCustomer']);
        $sub = $this->subscriptions->resolve('basic');
        $this->guard->assertAllowed($perms, $sub, 'GetCustomer');
        $this->assertCount(6, $this->guard->dashboardMenu());
        $this->assertSame('chat', AiDashboardMenu::items()[0]['key']);
    }

    public function test_session_and_memory(): void
    {
        $sessions = new AiSessionManager(new InMemorySessionProvider());
        $session = $sessions->start(new AiSession('tenant_a', 'ws_a', userId: 'u1'));
        $session = $sessions->attachConversation($session, 'conv_1');
        $session = $sessions->focus($session, 'BookReservation', 'Reservation.Create', 'CreateReservation');
        $this->assertSame('CreateReservation', $session->currentTool());
        $this->assertNotNull($sessions->current('tenant_a', 'u1'));

        $memory = new ConversationMemoryService(new InMemoryMemoryProvider());
        $saved = $memory->save($memory->getOrDefault('tenant_a'));
        $this->assertSame('tenant_a', $saved->tenantId());
    }

    public function test_enterprise_allows_all_tools(): void
    {
        $entitlement = $this->subscriptions->resolve(SubscriptionPlan::Enterprise);
        $this->assertTrue($entitlement->allowsTool('Anything'));
    }
}
