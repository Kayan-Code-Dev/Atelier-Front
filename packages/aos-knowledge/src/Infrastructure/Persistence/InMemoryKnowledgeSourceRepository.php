<?php

declare(strict_types=1);

namespace DressnMore\Aos\Knowledge\Infrastructure\Persistence;

use DressnMore\Aos\Knowledge\Domain\Repository\KnowledgeSourceRepositoryInterface;
use DressnMore\Aos\Knowledge\Domain\Source\KnowledgeSource;
use DressnMore\Aos\Knowledge\Domain\Source\SourceId;

final class InMemoryKnowledgeSourceRepository implements KnowledgeSourceRepositoryInterface
{
    /** @var array<string, KnowledgeSource> */
    private array $items = [];

    public function save(KnowledgeSource $source): void
    {
        $this->items[$source->id()->toString()] = $source;
    }

    public function find(SourceId $id): ?KnowledgeSource
    {
        return $this->items[$id->toString()] ?? null;
    }

    public function all(?string $tenantId = null): array
    {
        if ($tenantId === null) {
            return array_values($this->items);
        }

        return array_values(array_filter(
            $this->items,
            static fn (KnowledgeSource $s): bool => $s->tenantId() === null || $s->tenantId() === $tenantId
        ));
    }
}
