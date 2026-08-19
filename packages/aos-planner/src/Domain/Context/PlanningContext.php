<?php

declare(strict_types=1);

namespace DressnMore\Aos\Planner\Domain\Context;

/**
 * Immutable planning input context (opaque refs — no Context Engine coupling).
 */
final class PlanningContext
{
    /**
     * @param  array<string, scalar|null>  $attributes
     * @param  list<string>  $availableCapabilities
     * @param  list<string>  $availableToolIdentifiers
     */
    public function __construct(
        private readonly string $messageText,
        private readonly ?string $conversationId = null,
        private readonly ?string $tenantId = null,
        private readonly ?string $customerId = null,
        private readonly string $locale = 'ar',
        private readonly string $operatingMode = 'assistant',
        private readonly array $availableCapabilities = [],
        private readonly array $availableToolIdentifiers = [],
        private readonly array $attributes = [],
        private readonly string $correlationId = '',
    ) {}

    /**
     * @param  array<string, scalar|null>  $attributes
     * @param  list<string>  $availableCapabilities
     * @param  list<string>  $availableToolIdentifiers
     */
    public static function fromMessage(
        string $messageText,
        ?string $conversationId = null,
        ?string $tenantId = null,
        ?string $customerId = null,
        string $locale = 'ar',
        string $operatingMode = 'assistant',
        array $availableCapabilities = [],
        array $availableToolIdentifiers = [],
        array $attributes = [],
    ): self {
        return new self(
            $messageText,
            $conversationId,
            $tenantId,
            $customerId,
            $locale,
            $operatingMode,
            $availableCapabilities,
            $availableToolIdentifiers,
            $attributes,
            bin2hex(random_bytes(12)),
        );
    }

    public function messageText(): string
    {
        return $this->messageText;
    }

    public function conversationId(): ?string
    {
        return $this->conversationId;
    }

    public function tenantId(): ?string
    {
        return $this->tenantId;
    }

    public function customerId(): ?string
    {
        return $this->customerId;
    }

    public function locale(): string
    {
        return $this->locale;
    }

    public function operatingMode(): string
    {
        return $this->operatingMode;
    }

    /**
     * @return list<string>
     */
    public function availableCapabilities(): array
    {
        return $this->availableCapabilities;
    }

    /**
     * @return list<string>
     */
    public function availableToolIdentifiers(): array
    {
        return $this->availableToolIdentifiers;
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
        return $this->correlationId !== '' ? $this->correlationId : 'corr_unknown';
    }
}
