<?php

declare(strict_types=1);

namespace DressnMore\Aos\Conversation\Domain\Timeline;

use DateTimeImmutable;
use DressnMore\Aos\Conversation\Domain\Conversation\ConversationId;

/**
 * Immutable timeline entry for a Conversation.
 */
final class TimelineEvent
{
    /**
     * @param  array<string, scalar|null>  $payload
     */
    public function __construct(
        private readonly string $id,
        private readonly ConversationId $conversationId,
        private readonly TimelineEventType $type,
        private readonly array $payload = [],
        private readonly DateTimeImmutable $occurredAt = new DateTimeImmutable(),
    ) {}

    /**
     * @param  array<string, scalar|null>  $payload
     */
    public static function record(
        ConversationId $conversationId,
        TimelineEventType $type,
        array $payload = [],
    ): self {
        return new self(bin2hex(random_bytes(8)), $conversationId, $type, $payload);
    }

    public function id(): string
    {
        return $this->id;
    }

    public function conversationId(): ConversationId
    {
        return $this->conversationId;
    }

    public function type(): TimelineEventType
    {
        return $this->type;
    }

    /**
     * @return array<string, scalar|null>
     */
    public function payload(): array
    {
        return $this->payload;
    }

    public function occurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
