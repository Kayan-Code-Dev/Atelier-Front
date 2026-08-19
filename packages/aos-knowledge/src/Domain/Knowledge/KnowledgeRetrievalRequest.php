<?php

declare(strict_types=1);

namespace DressnMore\Aos\Knowledge\Domain\Knowledge;

/**
 * Opaque retrieval request (no Planner coupling).
 */
final class KnowledgeRetrievalRequest
{
    /**
     * @param  list<KnowledgeType>  $types
     * @param  list<string>  $tags
     * @param  array<string, scalar|null>  $attributes
     */
    public function __construct(
        private readonly ?string $tenantId,
        private readonly string $query = '',
        private readonly array $types = [],
        private readonly array $tags = [],
        private readonly ?string $ownerId = null,
        private readonly string $language = '',
        private readonly int $limit = 10,
        private readonly bool $includeGlobal = true,
        private readonly array $attributes = [],
        private readonly string $correlationId = '',
    ) {}

    /**
     * @param  list<KnowledgeType>  $types
     * @param  list<string>  $tags
     * @param  array<string, scalar|null>  $attributes
     */
    public static function create(
        ?string $tenantId,
        string $query = '',
        array $types = [],
        array $tags = [],
        ?string $ownerId = null,
        string $language = '',
        int $limit = 10,
        bool $includeGlobal = true,
        array $attributes = [],
        ?string $correlationId = null,
    ): self {
        return new self(
            $tenantId,
            $query,
            $types,
            $tags,
            $ownerId,
            $language,
            max(1, $limit),
            $includeGlobal,
            $attributes,
            $correlationId ?? bin2hex(random_bytes(12)),
        );
    }

    public function tenantId(): ?string
    {
        return $this->tenantId;
    }

    public function query(): string
    {
        return $this->query;
    }

    /** @return list<KnowledgeType> */
    public function types(): array
    {
        return $this->types;
    }

    /** @return list<string> */
    public function tags(): array
    {
        return $this->tags;
    }

    public function ownerId(): ?string
    {
        return $this->ownerId;
    }

    public function language(): string
    {
        return $this->language;
    }

    public function limit(): int
    {
        return $this->limit;
    }

    public function includeGlobal(): bool
    {
        return $this->includeGlobal;
    }

    /** @return array<string, scalar|null> */
    public function attributes(): array
    {
        return $this->attributes;
    }

    public function correlationId(): string
    {
        return $this->correlationId;
    }
}
