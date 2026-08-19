<?php

declare(strict_types=1);

namespace DressnMore\Aos\Knowledge\Domain\Repository;

use DressnMore\Aos\Knowledge\Domain\Collection\CollectionId;
use DressnMore\Aos\Knowledge\Domain\Collection\KnowledgeCollection;

interface KnowledgeCollectionRepositoryInterface
{
    public function save(KnowledgeCollection $collection): void;

    public function find(CollectionId $id): ?KnowledgeCollection;

    /**
     * @return list<KnowledgeCollection>
     */
    public function allVisibleTo(?string $tenantId): array;
}
