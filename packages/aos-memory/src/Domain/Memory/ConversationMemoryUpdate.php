<?php

declare(strict_types=1);

namespace DressnMore\Aos\Memory\Domain\Memory;

/**
 * Opaque conversation update input for the write pipeline (no Conversation Engine coupling).
 */
final class ConversationMemoryUpdate
{
    /**
     * @param  list<string>  $candidateHints  Pre-extracted facts/hints (never raw durable transcripts)
     * @param  array<string, scalar|null>  $attributes
     */
    public function __construct(
        private readonly string $tenantId,
        private readonly string $conversationId,
        private readonly ?string $customerId = null,
        private readonly ?string $messageId = null,
        private readonly string $userUtterance = '',
        private readonly array $candidateHints = [],
        private readonly array $attributes = [],
        private readonly string $correlationId = '',
    ) {}

    /**
     * @param  list<string>  $candidateHints
     * @param  array<string, scalar|null>  $attributes
     */
    public static function create(
        string $tenantId,
        string $conversationId,
        ?string $customerId = null,
        ?string $messageId = null,
        string $userUtterance = '',
        array $candidateHints = [],
        array $attributes = [],
        ?string $correlationId = null,
    ): self {
        return new self(
            $tenantId,
            $conversationId,
            $customerId,
            $messageId,
            $userUtterance,
            $candidateHints,
            $attributes,
            $correlationId ?? bin2hex(random_bytes(12)),
        );
    }

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

    public function messageId(): ?string
    {
        return $this->messageId;
    }

    public function userUtterance(): string
    {
        return $this->userUtterance;
    }

    /**
     * @return list<string>
     */
    public function candidateHints(): array
    {
        return $this->candidateHints;
    }

    /**
     * @return array<string, scalar|null>
     */
    public function attributes(): array
    {
        return $this->attributes;
    }

    public function correlationId(): string
    {
        return $this->correlationId;
    }
}
