<?php

declare(strict_types=1);

namespace DressnMore\Aos\Response\Domain\Response;

/**
 * Planning/conversation context for response generation (no LLM).
 */
final class ResponseContext
{
    /**
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        private readonly string $locale = 'ar',
        private readonly ?string $intent = null,
        private readonly ?string $goal = null,
        private readonly ?string $tenantId = null,
        private readonly ?string $conversationId = null,
        private readonly ?string $planId = null,
        private readonly string $correlationId = '',
        private readonly array $metadata = [],
    ) {}

    public function locale(): string { return $this->locale; }
    public function intent(): ?string { return $this->intent; }
    public function goal(): ?string { return $this->goal; }
    public function tenantId(): ?string { return $this->tenantId; }
    public function conversationId(): ?string { return $this->conversationId; }
    public function planId(): ?string { return $this->planId; }
    public function correlationId(): string
    {
        return $this->correlationId !== '' ? $this->correlationId : 'corr_unknown';
    }

    /** @return array<string, mixed> */
    public function metadata(): array { return $this->metadata; }
}
