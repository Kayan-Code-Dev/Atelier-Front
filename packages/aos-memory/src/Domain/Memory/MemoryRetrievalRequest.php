<?php

declare(strict_types=1);

namespace DressnMore\Aos\Memory\Domain\Memory;

/**
 * Opaque retrieval request (no Planner type coupling).
 */
final class MemoryRetrievalRequest
{
    /**
     * @param  list<MemoryType>  $types
     * @param  list<string>  $tags
     * @param  array<string, scalar|null>  $attributes
     */
    public function __construct(
        private readonly string $tenantId,
        private readonly ?string $customerId = null,
        private readonly ?string $conversationId = null,
        private readonly string $query = '',
        private readonly array $types = [],
        private readonly array $tags = [],
        private readonly int $limit = 20,
        private readonly array $attributes = [],
        private readonly string $correlationId = '',
    ) {}

    /**
     * @param  list<MemoryType>  $types
     * @param  list<string>  $tags
     * @param  array<string, scalar|null>  $attributes
     */
    public static function create(
        string $tenantId,
        ?string $customerId = null,
        ?string $conversationId = null,
        string $query = '',
        array $types = [],
        array $tags = [],
        int $limit = 20,
        array $attributes = [],
        ?string $correlationId = null,
    ): self {
        return new self(
            $tenantId,
            $customerId,
            $conversationId,
            $query,
            $types,
            $tags,
            max(1, $limit),
            $attributes,
            $correlationId ?? bin2hex(random_bytes(12)),
        );
    }

    public function tenantId(): string
    {
        return $this->tenantId;
    }

    public function customerId(): ?string
    {
        return $this->customerId;
    }

    public function conversationId(): ?string
    {
        return $this->conversationId;
    }

    public function query(): string
    {
        return $this->query;
    }

    /**
     * @return list<MemoryType>
     */
    public function types(): array
    {
        return $this->types;
    }

    /**
     * @return list<string>
     */
    public function tags(): array
    {
        return $this->tags;
    }

    public function limit(): int
    {
        return $this->limit;
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
