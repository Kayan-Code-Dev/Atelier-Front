<?php

declare(strict_types=1);

namespace DressnMore\Aos\Context\Domain\Conversation;

/**
 * Conversation slice for the Context Snapshot.
 */
final class ConversationContext
{
    public function __construct(
        private readonly ?ConversationRef $conversationId,
        private readonly ?string $state = null,
        private readonly ConversationOwnerKind $owner = ConversationOwnerKind::Unknown,
        private readonly OperatingMode $operatingMode = OperatingMode::Assistant,
        private readonly ?string $recentSummary = null,
    ) {}

    public static function none(): self
    {
        return new self(null);
    }

    public function conversationId(): ?ConversationRef
    {
        return $this->conversationId;
    }

    public function state(): ?string
    {
        return $this->state;
    }

    public function owner(): ConversationOwnerKind
    {
        return $this->owner;
    }

    public function operatingMode(): OperatingMode
    {
        return $this->operatingMode;
    }

    public function recentSummary(): ?string
    {
        return $this->recentSummary;
    }
}
