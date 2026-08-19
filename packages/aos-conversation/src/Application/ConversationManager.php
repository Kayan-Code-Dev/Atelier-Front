<?php

declare(strict_types=1);

namespace DressnMore\Aos\Conversation\Application;

use DressnMore\Aos\Conversation\Domain\Conversation\Conversation;
use DressnMore\Aos\Conversation\Domain\Conversation\ConversationFactory;
use DressnMore\Aos\Conversation\Domain\Conversation\ConversationId;
use DressnMore\Aos\Conversation\Domain\Conversation\ConversationOwnership;
use DressnMore\Aos\Conversation\Domain\Conversation\ConversationRepositoryInterface;
use DressnMore\Aos\Conversation\Domain\Conversation\TenantScopeId;
use DressnMore\Aos\Conversation\Domain\Events\ConversationDomainEvent;
use DressnMore\Aos\Conversation\Domain\Message\MessageAuthorKind;
use DressnMore\Aos\Conversation\Domain\Message\MessageContent;
use DressnMore\Aos\Conversation\Domain\Message\MessageDirection;
use DressnMore\Aos\Conversation\Domain\Timeline\TimelineEventType;
use DressnMore\Aos\Events\Contracts\EventBusInterface;
use RuntimeException;

/**
 * Application façade for Conversation lifecycle use cases.
 *
 * Persists via repository port and publishes domain events — no channels/AI/tools.
 */
final class ConversationManager
{
    public function __construct(
        private readonly ConversationFactory $factory,
        private readonly ConversationRepositoryInterface $repository,
        private readonly EventBusInterface $eventBus,
        private readonly ConversationLifecycle $lifecycle = new ConversationLifecycle(),
    ) {}

    public function startConversation(
        TenantScopeId $tenantScopeId,
        ConversationOwnership $ownership = ConversationOwnership::AI,
        bool $activate = true,
    ): Conversation {
        $conversation = $this->factory->create($tenantScopeId, $ownership);

        if ($activate) {
            $this->lifecycle->activate($conversation);
        }

        return $this->persistAndPublish($conversation);
    }

    public function resumeConversation(ConversationId $id): Conversation
    {
        $conversation = $this->require($id);
        $this->lifecycle->resume($conversation);

        return $this->persistAndPublish($conversation);
    }

    public function pauseConversation(ConversationId $id): Conversation
    {
        $conversation = $this->require($id);
        $this->lifecycle->pause($conversation);

        return $this->persistAndPublish($conversation);
    }

    public function closeConversation(ConversationId $id): Conversation
    {
        $conversation = $this->require($id);
        $this->lifecycle->close($conversation);

        return $this->persistAndPublish($conversation);
    }

    public function archiveConversation(ConversationId $id): Conversation
    {
        $conversation = $this->require($id);
        $this->lifecycle->archive($conversation);

        return $this->persistAndPublish($conversation);
    }

    public function transferOwnership(ConversationId $id, ConversationOwnership $to): Conversation
    {
        $conversation = $this->require($id);
        $this->lifecycle->transferOwnership($conversation, $to);

        return $this->persistAndPublish($conversation);
    }

    public function assignHuman(ConversationId $id): Conversation
    {
        $conversation = $this->require($id);
        $this->lifecycle->assignHuman($conversation);

        return $this->persistAndPublish($conversation);
    }

    public function returnToAi(ConversationId $id): Conversation
    {
        $conversation = $this->require($id);
        $this->lifecycle->returnToAi($conversation);

        return $this->persistAndPublish($conversation);
    }

    public function addMessage(
        ConversationId $id,
        MessageDirection $direction,
        MessageAuthorKind $authorKind,
        string $text,
    ): Conversation {
        $conversation = $this->require($id);
        $this->lifecycle->addMessage(
            $conversation,
            $direction,
            $authorKind,
            new MessageContent($text),
        );

        return $this->persistAndPublish($conversation);
    }

    /**
     * @param  array<string, scalar|null>  $payload
     */
    public function addTimelineEvent(
        ConversationId $id,
        TimelineEventType $type,
        array $payload = [],
    ): Conversation {
        $conversation = $this->require($id);
        $this->lifecycle->addTimelineEvent($conversation, $type, $payload);

        return $this->persistAndPublish($conversation);
    }

    public function generateSummaryPlaceholder(ConversationId $id, string $text = 'Summary pending.'): Conversation
    {
        $conversation = $this->require($id);
        $this->lifecycle->generateSummaryPlaceholder($conversation, $text);

        return $this->persistAndPublish($conversation);
    }

    public function get(ConversationId $id): ?Conversation
    {
        return $this->repository->findById($id);
    }

    private function require(ConversationId $id): Conversation
    {
        $conversation = $this->repository->findById($id);
        if ($conversation === null) {
            throw new RuntimeException(sprintf('Conversation [%s] not found.', $id->toString()));
        }

        return $conversation;
    }

    private function persistAndPublish(Conversation $conversation): Conversation
    {
        $events = $conversation->pullDomainEvents();
        $this->repository->save($conversation);

        foreach ($events as $event) {
            /** @var ConversationDomainEvent $event */
            $this->eventBus->publish($event);
        }

        return $conversation;
    }
}
