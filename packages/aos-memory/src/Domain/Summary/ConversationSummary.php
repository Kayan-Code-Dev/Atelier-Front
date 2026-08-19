<?php

declare(strict_types=1);

namespace DressnMore\Aos\Memory\Domain\Summary;

use DateTimeImmutable;

/**
 * Conversation summary artifact (classified summary — not raw messages).
 */
final class ConversationSummary
{
    /**
     * @param  list<string>  $facts
     */
    public function __construct(
        private readonly string $tenantId,
        private readonly string $conversationId,
        private readonly ?string $customerId,
        private readonly SummaryKind $kind,
        private readonly string $text,
        private readonly array $facts = [],
        private readonly DateTimeImmutable $generatedAt = new DateTimeImmutable(),
    ) {}

    public function tenantId(): string
    {
        return $this->tenantId;
    }

    public function conversationId(): string
    {
        return $this->conversationId;
    }

    public function customerId(): ?string
    {
        return $this->customerId;
    }

    public function kind(): SummaryKind
    {
        return $this->kind;
    }

    public function text(): string
    {
        return $this->text;
    }

    /**
     * @return list<string>
     */
    public function facts(): array
    {
        return $this->facts;
    }

    public function generatedAt(): DateTimeImmutable
    {
        return $this->generatedAt;
    }
}
