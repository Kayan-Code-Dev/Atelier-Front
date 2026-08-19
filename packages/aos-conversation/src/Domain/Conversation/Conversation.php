<?php

declare(strict_types=1);

namespace DressnMore\Aos\Conversation\Domain\Conversation;

use DateTimeImmutable;
use DressnMore\Aos\Conversation\Domain\Conversation\Exceptions\ConversationLifecycleException;
use DressnMore\Aos\Conversation\Domain\Conversation\Policies\OwnershipPolicy;
use DressnMore\Aos\Conversation\Domain\Events\ConversationArchived;
use DressnMore\Aos\Conversation\Domain\Events\ConversationClosed;
use DressnMore\Aos\Conversation\Domain\Events\ConversationDomainEvent;
use DressnMore\Aos\Conversation\Domain\Events\ConversationOwnerChanged;
use DressnMore\Aos\Conversation\Domain\Events\ConversationPaused;
use DressnMore\Aos\Conversation\Domain\Events\ConversationResumed;
use DressnMore\Aos\Conversation\Domain\Events\ConversationReturnedToAI;
use DressnMore\Aos\Conversation\Domain\Events\ConversationSessionEnded;
use DressnMore\Aos\Conversation\Domain\Events\ConversationSessionStarted;
use DressnMore\Aos\Conversation\Domain\Events\ConversationStarted;
use DressnMore\Aos\Conversation\Domain\Events\ConversationStateChanged;
use DressnMore\Aos\Conversation\Domain\Events\ConversationTransferred;
use DressnMore\Aos\Conversation\Domain\Events\MessageAdded;
use DressnMore\Aos\Conversation\Domain\Events\TimelineEventRecorded;
use DressnMore\Aos\Conversation\Domain\Message\ConversationMessage;
use DressnMore\Aos\Conversation\Domain\Message\MessageAuthorKind;
use DressnMore\Aos\Conversation\Domain\Message\MessageContent;
use DressnMore\Aos\Conversation\Domain\Message\MessageDirection;
use DressnMore\Aos\Conversation\Domain\Session\ConversationSession;
use DressnMore\Aos\Conversation\Domain\Timeline\Timeline;
use DressnMore\Aos\Conversation\Domain\Timeline\TimelineEvent;
use DressnMore\Aos\Conversation\Domain\Timeline\TimelineEventType;

/**
 * Conversation aggregate root — owns lifecycle, ownership, messages, sessions, and timeline.
 *
 * Intentionally channel-, tool-, and AI-agnostic.
 */
final class Conversation
{
    private ConversationStatus $status;

    private ConversationOwnership $ownership;

    private readonly Timeline $timeline;

    /** @var list<ConversationMessage> */
    private array $messages = [];

    /** @var list<ConversationSession> */
    private array $sessions = [];

    private ?string $summaryPlaceholder = null;

    private readonly DateTimeImmutable $createdAt;

    private DateTimeImmutable $updatedAt;

    /** @var list<ConversationDomainEvent> */
    private array $domainEvents = [];

    private function __construct(
        private readonly ConversationId $id,
        private readonly TenantScopeId $tenantScopeId,
        ConversationOwnership $ownership,
        ConversationStatus $status,
        private readonly ConversationStateMachine $stateMachine = new ConversationStateMachine(),
        private readonly OwnershipPolicy $ownershipPolicy = new OwnershipPolicy(),
        ?DateTimeImmutable $createdAt = null,
    ) {
        $this->ownership = $ownership;
        $this->status = $status;
        $this->timeline = new Timeline();
        $this->createdAt = $createdAt ?? new DateTimeImmutable();
        $this->updatedAt = $this->createdAt;
    }

    /**
     * Factory entry used by ConversationFactory — records start on the timeline.
     */
    public static function startNew(
        ConversationId $id,
        TenantScopeId $tenantScopeId,
        ConversationOwnership $ownership = ConversationOwnership::AI,
    ): self {
        $conversation = new self($id, $tenantScopeId, $ownership, ConversationStatus::New);
        $conversation->recordTimeline(TimelineEventType::ConversationStarted, [
            'ownership' => $ownership->value,
        ]);
        $conversation->raise(new ConversationStarted($id, $tenantScopeId, $ownership));

        return $conversation;
    }

    public function id(): ConversationId
    {
        return $this->id;
    }

    public function tenantScopeId(): TenantScopeId
    {
        return $this->tenantScopeId;
    }

    public function status(): ConversationStatus
    {
        return $this->status;
    }

    public function ownership(): ConversationOwnership
    {
        return $this->ownership;
    }

    public function timeline(): Timeline
    {
        return $this->timeline;
    }

    /**
     * @return list<ConversationMessage>
     */
    public function messages(): array
    {
        return $this->messages;
    }

    /**
     * @return list<ConversationSession>
     */
    public function sessions(): array
    {
        return $this->sessions;
    }

    public function summaryPlaceholder(): ?string
    {
        return $this->summaryPlaceholder;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function openSession(): ?ConversationSession
    {
        foreach (array_reverse($this->sessions) as $session) {
            if ($session->isOpen()) {
                return $session;
            }
        }

        return null;
    }

    /**
     * New → Active and opens the first session when none exists.
     */
    public function activate(): void
    {
        $this->transitionTo(ConversationStatus::Active);

        if ($this->openSession() === null) {
            $this->startSession();
        }
    }

    public function resume(): void
    {
        if ($this->status === ConversationStatus::Paused) {
            $this->transitionTo(ConversationStatus::Active);
            $this->recordTimeline(TimelineEventType::Resumed);
            $this->raise(new ConversationResumed($this->id));

            return;
        }

        if ($this->status === ConversationStatus::Resolved) {
            $this->transitionTo(ConversationStatus::Active);
            $this->recordTimeline(TimelineEventType::Resumed);
            $this->raise(new ConversationResumed($this->id));

            return;
        }

        if ($this->status === ConversationStatus::New) {
            $this->activate();
            $this->raise(new ConversationResumed($this->id));

            return;
        }

        // Already interactive — idempotent resume.
        if (in_array($this->status, [
            ConversationStatus::Active,
            ConversationStatus::WaitingCustomer,
            ConversationStatus::WaitingHuman,
            ConversationStatus::HumanHandling,
        ], true)) {
            return;
        }

        $this->stateMachine->assertCanTransition($this->status, ConversationStatus::Active);
    }

    public function pause(): void
    {
        $this->transitionTo(ConversationStatus::Paused);
        $this->recordTimeline(TimelineEventType::Paused);
        $this->raise(new ConversationPaused($this->id));
    }

    public function waitForCustomer(): void
    {
        $this->transitionTo(ConversationStatus::WaitingCustomer);
    }

    public function escalateToHuman(): void
    {
        $this->transitionTo(ConversationStatus::WaitingHuman);
        $this->recordTimeline(TimelineEventType::Escalated, [
            'from_ownership' => $this->ownership->value,
        ]);
    }

    public function markResolved(): void
    {
        $this->transitionTo(ConversationStatus::Resolved);
    }

    public function close(): void
    {
        if ($session = $this->openSession()) {
            $this->endSession($session);
        }

        $this->transitionTo(ConversationStatus::Closed);
        $this->recordTimeline(TimelineEventType::Closed);
        $this->raise(new ConversationClosed($this->id));
    }

    public function archive(): void
    {
        $this->transitionTo(ConversationStatus::Archived);
        $this->recordTimeline(TimelineEventType::Archived);
        $this->raise(new ConversationArchived($this->id));
    }

    public function transferOwnership(ConversationOwnership $to): void
    {
        $from = $this->ownership;
        $this->ownershipPolicy->assertCanChange($from, $to, $this->status);

        if ($from === $to) {
            return;
        }

        $this->ownership = $to;
        $this->touch();
        $this->recordTimeline(TimelineEventType::OwnerChanged, [
            'from' => $from->value,
            'to' => $to->value,
        ]);
        $this->raise(new ConversationOwnerChanged($this->id, $from, $to));
        $this->raise(new ConversationTransferred($this->id, $from, $to));
    }

    public function assignHuman(): void
    {
        $this->transferOwnership($this->ownershipPolicy->ownerForAssignHuman());

        if ($this->status !== ConversationStatus::HumanHandling) {
            if ($this->stateMachine->canTransition($this->status, ConversationStatus::HumanHandling)) {
                $this->transitionTo(ConversationStatus::HumanHandling);
            } elseif ($this->stateMachine->canTransition($this->status, ConversationStatus::WaitingHuman)) {
                $this->transitionTo(ConversationStatus::WaitingHuman);
            }
        }
    }

    public function returnToAi(): void
    {
        $from = $this->ownership;
        $this->transferOwnership($this->ownershipPolicy->ownerForReturnToAi());
        $this->recordTimeline(TimelineEventType::ReturnedToAi, [
            'from' => $from->value,
        ]);
        $this->raise(new ConversationReturnedToAI($this->id));

        if ($this->status === ConversationStatus::HumanHandling
            || $this->status === ConversationStatus::WaitingHuman
        ) {
            $this->transitionTo(ConversationStatus::Active);
        }
    }

    public function enableSharedAssist(): void
    {
        $this->transferOwnership($this->ownershipPolicy->ownerForSharedAssist());
    }

    public function addMessage(
        MessageDirection $direction,
        MessageAuthorKind $authorKind,
        MessageContent $content,
    ): ConversationMessage {
        if ($this->status->isTerminal()) {
            throw ConversationLifecycleException::cannotMessage('conversation is terminal');
        }

        if ($this->status === ConversationStatus::Paused) {
            throw ConversationLifecycleException::cannotMessage('conversation is paused');
        }

        if ($this->status === ConversationStatus::New) {
            $this->activate();
        }

        $message = ConversationMessage::create($this->id, $direction, $authorKind, $content);
        $this->messages[] = $message;
        $this->touch();
        $this->recordTimeline(TimelineEventType::MessageAdded, [
            'message_id' => $message->id()->toString(),
            'direction' => $direction->value,
            'author' => $authorKind->value,
        ]);
        $this->raise(new MessageAdded($this->id, $message->id()));

        return $message;
    }

    /**
     * @param  array<string, scalar|null>  $payload
     */
    public function addTimelineEvent(TimelineEventType $type, array $payload = []): TimelineEvent
    {
        return $this->recordTimeline($type, $payload);
    }

    /**
     * Placeholder for a later summarization module — stores opaque text only.
     */
    public function generateSummaryPlaceholder(string $text): void
    {
        $trimmed = trim($text);
        if ($trimmed === '') {
            $trimmed = 'Summary pending.';
        }

        $this->summaryPlaceholder = $trimmed;
        $this->touch();
        $this->recordTimeline(TimelineEventType::SummaryGenerated, [
            'length' => strlen($trimmed),
        ]);
    }

    public function startSession(): ConversationSession
    {
        if ($this->openSession() !== null) {
            throw ConversationLifecycleException::sessionAlreadyOpen();
        }

        if ($this->status->isTerminal()) {
            throw ConversationLifecycleException::cannotStartSession('conversation is terminal');
        }

        $session = ConversationSession::start($this->id);
        $this->sessions[] = $session;
        $this->touch();
        $this->recordTimeline(TimelineEventType::SessionStarted, [
            'session_id' => $session->id()->toString(),
        ]);
        $this->raise(new ConversationSessionStarted($this->id, $session->id()));

        return $session;
    }

    public function endCurrentSession(): void
    {
        $session = $this->openSession();
        if ($session === null) {
            throw ConversationLifecycleException::noOpenSession();
        }

        $this->endSession($session);
    }

    /**
     * @return list<ConversationDomainEvent>
     */
    public function pullDomainEvents(): array
    {
        $events = $this->domainEvents;
        $this->domainEvents = [];

        return $events;
    }

    private function endSession(ConversationSession $session): void
    {
        $session->end();
        $this->touch();
        $this->recordTimeline(TimelineEventType::SessionEnded, [
            'session_id' => $session->id()->toString(),
        ]);
        $this->raise(new ConversationSessionEnded($this->id, $session->id()));
    }

    private function transitionTo(ConversationStatus $to): void
    {
        $from = $this->status;
        if ($from === $to) {
            return;
        }

        $this->stateMachine->assertCanTransition($from, $to);
        $this->status = $to;
        $this->touch();
        $this->recordTimeline(TimelineEventType::StateChanged, [
            'from' => $from->value,
            'to' => $to->value,
        ]);
        $this->raise(new ConversationStateChanged($this->id, $from, $to));
    }

    /**
     * @param  array<string, scalar|null>  $payload
     */
    private function recordTimeline(TimelineEventType $type, array $payload = []): TimelineEvent
    {
        $event = TimelineEvent::record($this->id, $type, $payload);
        $this->timeline->record($event);
        $this->raise(new TimelineEventRecorded($this->id, $event->id(), $type));

        return $event;
    }

    private function raise(ConversationDomainEvent $event): void
    {
        $this->domainEvents[] = $event;
    }

    private function touch(): void
    {
        $this->updatedAt = new DateTimeImmutable();
    }
}
