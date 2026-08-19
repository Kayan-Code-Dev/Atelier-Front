<?php

declare(strict_types=1);

namespace DressnMore\Aos\Knowledge\Domain\Repository;

use DressnMore\Aos\Knowledge\Domain\Collection\CollectionId;
use DressnMore\Aos\Knowledge\Domain\Knowledge\KnowledgeDocument;
use DressnMore\Aos\Knowledge\Domain\Knowledge\KnowledgeId;
use DressnMore\Aos\Knowledge\Domain\Knowledge\KnowledgeLifecycleStatus;
use DressnMore\Aos\Knowledge\Domain\Knowledge\KnowledgeType;

interface KnowledgeDocumentRepositoryInterface
{
    public function save(KnowledgeDocument $document): void;

    public function find(KnowledgeId $id): ?KnowledgeDocument;

    /**
     * @param  list<KnowledgeType>  $types
     * @param  list<KnowledgeLifecycleStatus>  $statuses
     * @return list<KnowledgeDocument>
     */
    public function findForTenant(
        ?string $tenantId,
        array $types = [],
        array $statuses = [],
        ?CollectionId $collectionId = null,
        int $limit = 200,
        bool $includeGlobal = true,
    ): array;

    public function delete(KnowledgeId $id): void;
}
