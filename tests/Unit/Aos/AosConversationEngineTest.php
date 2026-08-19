<?php

declare(strict_types=1);

namespace Tests\Unit\Aos;

use DressnMore\Aos\Conversation\Application\ConversationManager;
use DressnMore\Aos\Conversation\Architecture\ConversationScopeDecision;
use DressnMore\Aos\Conversation\Domain\Conversation\ConversationOwnership;
use DressnMore\Aos\Conversation\Domain\Conversation\ConversationStatus;
use DressnMore\Aos\Conversation\Domain\Conversation\Exceptions\IllegalStateTransition;
use DressnMore\Aos\Conversation\Domain\Conversation\TenantScopeId;
use DressnMore\Aos\Conversation\Domain\Message\MessageAuthorKind;
use DressnMore\Aos\Conversation\Domain\Message\MessageDirection;
use DressnMore\Aos\Core\Module\Contracts\ModuleRegistryInterface;
use Tests\TestCase;

final class AosConversationEngineTest extends TestCase
{
    public function test_conversation_module_is_registered(): void
    {
        /** @var ModuleRegistryInterface $registry */
        $registry = $this->app->make(ModuleRegistryInterface::class);

        $this->assertTrue($registry->has('aos.conversation'));
    }

    public function test_manager_start_message_and_close(): void
    {
        /** @var ConversationManager $manager */
        $manager = $this->app->make(ConversationManager::class);

        $conversation = $manager->startConversation(TenantScopeId::fromString('shop-1'));
        $this->assertSame(ConversationStatus::Active, $conversation->status());

        $conversation = $manager->addMessage(
            $conversation->id(),
            MessageDirection::Inbound,
            MessageAuthorKind::Customer,
            'I need a fitting',
        );
        $this->assertCount(1, $conversation->messages());

        $conversation = $manager->assignHuman($conversation->id());
        $this->assertSame(ConversationOwnership::Human, $conversation->ownership());

        $conversation = $manager->returnToAi($conversation->id());
        $this->assertSame(ConversationOwnership::AI, $conversation->ownership());

        $conversation = $manager->closeConversation($conversation->id());
        $this->assertSame(ConversationStatus::Closed, $conversation->status());

        $conversation = $manager->archiveConversation($conversation->id());
        $this->assertSame(ConversationStatus::Archived, $conversation->status());
    }

    public function test_illegal_transition_via_manager_archive_active(): void
    {
        /** @var ConversationManager $manager */
        $manager = $this->app->make(ConversationManager::class);
        $conversation = $manager->startConversation(TenantScopeId::fromString('shop-2'));

        $this->expectException(IllegalStateTransition::class);
        $manager->archiveConversation($conversation->id());
    }

    public function test_sprint2_scope_excludes_product_integrations(): void
    {
        $excluded = ConversationScopeDecision::excludedConcerns();

        $this->assertContains('openai', $excluded);
        $this->assertContains('whatsapp', $excluded);
        $this->assertContains('business_tools', $excluded);
        $this->assertContains('planner', $excluded);
        $this->assertContains('knowledge', $excluded);
        $this->assertSame(['dressnmore/aos-conversation'], ConversationScopeDecision::includedPackages());
    }
}
