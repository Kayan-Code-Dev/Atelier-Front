<?php

declare(strict_types=1);

namespace DressnMore\Aos\Conversation\Domain\Session;

use DressnMore\Aos\Conversation\Domain\Conversation\ConversationId;
use DateTimeImmutable;

/**
 * Independent working session belonging to a Conversation.
 * A Conversation may have many sessions over its lifetime.
 */
final class ConversationSession
{
    private ?DateTimeImmutable $endedAt = null;

    public function __construct(
        private readonly SessionId $id,
        private readonly ConversationId $conversationId,
        private readonly DateTimeImmutable $startedAt = new DateTimeImmutable(),
    ) {}

    public static function start(ConversationId $conversationId): self
    {
        return new self(SessionId::generate(), $conversationId);
    }

    public function id(): SessionId
    {
        return $this->id;
    }

    public function conversationId(): ConversationId
    {
        return $this->conversationId;
    }

    public function startedAt(): DateTimeImmutable
    {
        return $this->startedAt;
    }

    public function endedAt(): ?DateTimeImmutable
    {
        return $this->endedAt;
    }

    public function isOpen(): bool
    {
        return $this->endedAt === null;
    }

    public function end(?DateTimeImmutable $at = null): void
    {
        if ($this->endedAt !== null) {
            return;
        }

        $this->endedAt = $at ?? new DateTimeImmutable();
    }
}
