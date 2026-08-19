<?php

declare(strict_types=1);

namespace DressnMore\Aos\Knowledge\Domain\Source;

/**
 * Registered knowledge source (adapter hook for future ingest).
 */
final class KnowledgeSource
{
    /**
     * @param  array<string, scalar|null>  $config
     */
    public function __construct(
        private readonly SourceId $id,
        private readonly KnowledgeSourceType $type,
        private readonly string $name,
        private readonly ?string $tenantId = null,
        private readonly array $config = [],
        private readonly bool $enabled = true,
    ) {}

    public function id(): SourceId
    {
        return $this->id;
    }

    public function type(): KnowledgeSourceType
    {
        return $this->type;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function tenantId(): ?string
    {
        return $this->tenantId;
    }

    /**
     * @return array<string, scalar|null>
     */
    public function config(): array
    {
        return $this->config;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }
}
