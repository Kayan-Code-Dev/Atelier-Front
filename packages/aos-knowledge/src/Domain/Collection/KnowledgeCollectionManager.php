<?php

declare(strict_types=1);

namespace DressnMore\Aos\Knowledge\Domain\Collection;

use DressnMore\Aos\Knowledge\Domain\Repository\KnowledgeCollectionRepositoryInterface;

final class KnowledgeCollectionManager
{
    public function __construct(
        private readonly KnowledgeCollectionRepositoryInterface $collections,
    ) {}

    public function register(KnowledgeCollection $collection): KnowledgeCollection
    {
        $this->collections->save($collection);

        return $collection;
    }

    public function get(CollectionId $id): ?KnowledgeCollection
    {
        return $this->collections->find($id);
    }

    /**
     * @return list<KnowledgeCollection>
     */
    public function visibleTo(?string $tenantId): array
    {
        return $this->collections->allVisibleTo($tenantId);
    }
}
