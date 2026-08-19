<?php

declare(strict_types=1);

namespace DressnMore\Aos\Conversation\Application;

use DressnMore\Aos\Conversation\Domain\Conversation\Conversation;
use DressnMore\Aos\Conversation\Domain\Conversation\ConversationOwnership;
use DressnMore\Aos\Conversation\Domain\Message\ConversationMessage;
use DressnMore\Aos\Conversation\Domain\Message\MessageAuthorKind;
use DressnMore\Aos\Conversation\Domain\Message\MessageContent;
use DressnMore\Aos\Conversation\Domain\Message\MessageDirection;
use DressnMore\Aos\Conversation\Domain\Timeline\TimelineEvent;
use DressnMore\Aos\Conversation\Domain\Timeline\TimelineEventType;

/**
 * Domain application service coordinating Conversation lifecycle operations.
 */
final class ConversationLifecycle
{
    public function activate(Conversation $conversation): void
    {
        $conversation->activate();
    }

    public function resume(Conversation $conversation): void
    {
        $conversation->resume();
    }

    public function pause(Conversation $conversation): void
    {
        $conversation->pause();
    }

    public function close(Conversation $conversation): void
    {
        $conversation->close();
    }

    public function archive(Conversation $conversation): void
    {
        $conversation->archive();
    }

    public function transferOwnership(Conversation $conversation, ConversationOwnership $to): void
    {
        $conversation->transferOwnership($to);
    }

    public function assignHuman(Conversation $conversation): void
    {
        $conversation->assignHuman();
    }

    public function returnToAi(Conversation $conversation): void
    {
        $conversation->returnToAi();
    }

    public function addMessage(
        Conversation $conversation,
        MessageDirection $direction,
        MessageAuthorKind $authorKind,
        MessageContent $content,
    ): ConversationMessage {
        return $conversation->addMessage($direction, $authorKind, $content);
    }

    /**
     * @param  array<string, scalar|null>  $payload
     */
    public function addTimelineEvent(
        Conversation $conversation,
        TimelineEventType $type,
        array $payload = [],
    ): TimelineEvent {
        return $conversation->addTimelineEvent($type, $payload);
    }

    public function generateSummaryPlaceholder(Conversation $conversation, string $text): void
    {
        $conversation->generateSummaryPlaceholder($text);
    }
}
