<?php

declare(strict_types=1);

namespace DressnMore\Aos\Conversation\Tests\Unit;

use DressnMore\Aos\Conversation\Domain\Conversation\ConversationFactory;
use DressnMore\Aos\Conversation\Domain\Conversation\ConversationOwnership;
use DressnMore\Aos\Conversation\Domain\Conversation\ConversationStatus;
use DressnMore\Aos\Conversation\Domain\Conversation\Exceptions\IllegalStateTransition;
use DressnMore\Aos\Conversation\Domain\Conversation\Exceptions\OwnershipPolicyViolation;
use DressnMore\Aos\Conversation\Domain\Conversation\TenantScopeId;
use DressnMore\Aos\Conversation\Domain\Message\MessageAuthorKind;
use DressnMore\Aos\Conversation\Domain\Message\MessageContent;
use DressnMore\Aos\Conversation\Domain\Message\MessageDirection;
use DressnMore\Aos\Conversation\Domain\Timeline\TimelineEventType;
use PHPUnit\Framework\TestCase;

final class ConversationEngineTest extends TestCase
{
    public function test_conversation_creation(): void
    {
        $conversation = (new ConversationFactory())->createForTenant('tenant-a');

        $this->assertSame(ConversationStatus::New, $conversation->status());
        $this->assertSame(ConversationOwnership::AI, $conversation->ownership());
        $this->assertTrue($conversation->timeline()->hasType(TimelineEventType::ConversationStarted));
        $this->assertNotEmpty($conversation->pullDomainEvents());
    }

    public function test_state_transitions_activate_pause_resume_close_archive(): void
    {
        $conversation = (new ConversationFactory())->createForTenant('tenant-a');
        $conversation->pullDomainEvents();

        $conversation->activate();
        $this->assertSame(ConversationStatus::Active, $conversation->status());
        $this->assertNotNull($conversation->openSession());

        $conversation->pause();
        $this->assertSame(ConversationStatus::Paused, $conversation->status());

        $conversation->resume();
        $this->assertSame(ConversationStatus::Active, $conversation->status());

        $conversation->close();
        $this->assertSame(ConversationStatus::Closed, $conversation->status());
        $this->assertNull($conversation->openSession());

        $conversation->archive();
        $this->assertSame(ConversationStatus::Archived, $conversation->status());
    }

    public function test_illegal_state_transition(): void
    {
        $conversation = (new ConversationFactory())->createForTenant('tenant-a');

        $this->expectException(IllegalStateTransition::class);
        $conversation->archive();
    }

    public function test_ownership_rules_assign_human_and_return_to_ai(): void
    {
        $conversation = (new ConversationFactory())->createForTenant('tenant-a');
        $conversation->activate();
        $conversation->pullDomainEvents();

        $conversation->assignHuman();
        $this->assertSame(ConversationOwnership::Human, $conversation->ownership());
        $this->assertSame(ConversationStatus::HumanHandling, $conversation->status());

        $conversation->returnToAi();
        $this->assertSame(ConversationOwnership::AI, $conversation->ownership());
        $this->assertSame(ConversationStatus::Active, $conversation->status());
        $this->assertTrue($conversation->timeline()->hasType(TimelineEventType::ReturnedToAi));
    }

    public function test_ownership_cannot_transfer_to_system_mid_flight(): void
    {
        $conversation = (new ConversationFactory())->createForTenant('tenant-a');
        $conversation->activate();

        $this->expectException(OwnershipPolicyViolation::class);
        $conversation->transferOwnership(ConversationOwnership::System);
    }

    public function test_timeline_recording_on_message_and_summary(): void
    {
        $conversation = (new ConversationFactory())->createForTenant('tenant-a');
        $conversation->activate();

        $conversation->addMessage(
            MessageDirection::Inbound,
            MessageAuthorKind::Customer,
            new MessageContent('Hello'),
        );
        $conversation->generateSummaryPlaceholder('Customer greeted.');

        $this->assertTrue($conversation->timeline()->hasType(TimelineEventType::MessageAdded));
        $this->assertTrue($conversation->timeline()->hasType(TimelineEventType::SummaryGenerated));
        $this->assertSame('Customer greeted.', $conversation->summaryPlaceholder());
        $this->assertCount(1, $conversation->messages());
    }

    public function test_session_creation_and_second_session_after_end(): void
    {
        $conversation = (new ConversationFactory())->create(
            TenantScopeId::fromString('tenant-b'),
            ConversationOwnership::AI,
        );
        $conversation->activate();
        $first = $conversation->openSession();
        $this->assertNotNull($first);

        $conversation->endCurrentSession();
        $this->assertFalse($first->isOpen());

        $second = $conversation->startSession();
        $this->assertTrue($second->isOpen());
        $this->assertCount(2, $conversation->sessions());
        $this->assertTrue($conversation->timeline()->hasType(TimelineEventType::SessionStarted));
        $this->assertTrue($conversation->timeline()->hasType(TimelineEventType::SessionEnded));
    }

    public function test_cannot_message_while_paused(): void
    {
        $conversation = (new ConversationFactory())->createForTenant('tenant-a');
        $conversation->activate();
        $conversation->pause();

        $this->expectException(\DressnMore\Aos\Conversation\Domain\Conversation\Exceptions\ConversationLifecycleException::class);
        $conversation->addMessage(
            MessageDirection::Outbound,
            MessageAuthorKind::AIAgent,
            new MessageContent('Nope'),
        );
    }
}
