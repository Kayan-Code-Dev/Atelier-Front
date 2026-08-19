<?php

declare(strict_types=1);

namespace DressnMore\Aos\Conversation\Domain\Message;

use DateTimeImmutable;
use DressnMore\Aos\Conversation\Domain\Conversation\ConversationId;

/**
 * One utterance or system notice inside a Conversation.
 */
final class ConversationMessage
{
    public function __construct(
        private readonly MessageId $id,
        private readonly ConversationId $conversationId,
        private readonly MessageDirection $direction,
        private readonly MessageAuthorKind $authorKind,
        private readonly MessageContent $content,
        private readonly DateTimeImmutable $createdAt = new DateTimeImmutable(),
    ) {}

    public static function create(
        ConversationId $conversationId,
        MessageDirection $direction,
        MessageAuthorKind $authorKind,
        MessageContent $content,
    ): self {
        return new self(
            MessageId::generate(),
            $conversationId,
            $direction,
            $authorKind,
            $content,
        );
    }

    public function id(): MessageId
    {
        return $this->id;
    }

    public function conversationId(): ConversationId
    {
        return $this->conversationId;
    }

    public function direction(): MessageDirection
    {
        return $this->direction;
    }

    public function authorKind(): MessageAuthorKind
    {
        return $this->authorKind;
    }

    public function content(): MessageContent
    {
        return $this->content;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
}
