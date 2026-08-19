<?php

declare(strict_types=1);

namespace DressnMore\Aos\Knowledge\Infrastructure\Persistence;

use DressnMore\Aos\Knowledge\Domain\Collection\CollectionId;
use DressnMore\Aos\Knowledge\Domain\Collection\KnowledgeCollection;
use DressnMore\Aos\Knowledge\Domain\Repository\KnowledgeCollectionRepositoryInterface;

final class InMemoryKnowledgeCollectionRepository implements KnowledgeCollectionRepositoryInterface
{
    /** @var array<string, KnowledgeCollection> */
    private array $items = [];

    public function save(KnowledgeCollection $collection): void
    {
        $this->items[$collection->id()->toString()] = $collection;
    }

    public function find(CollectionId $id): ?KnowledgeCollection
    {
        return $this->items[$id->toString()] ?? null;
    }

    public function allVisibleTo(?string $tenantId): array
    {
        return array_values(array_filter(
            $this->items,
            static fn (KnowledgeCollection $c): bool => $c->isVisibleToTenant($tenantId)
        ));
    }
}
