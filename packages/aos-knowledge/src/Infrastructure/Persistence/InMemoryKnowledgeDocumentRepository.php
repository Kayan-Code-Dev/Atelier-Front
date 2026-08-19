<?php

declare(strict_types=1);

namespace DressnMore\Aos\Knowledge\Infrastructure\Persistence;

use DressnMore\Aos\Knowledge\Domain\Collection\CollectionId;
use DressnMore\Aos\Knowledge\Domain\Knowledge\KnowledgeDocument;
use DressnMore\Aos\Knowledge\Domain\Knowledge\KnowledgeId;
use DressnMore\Aos\Knowledge\Domain\Knowledge\KnowledgeLifecycleStatus;
use DressnMore\Aos\Knowledge\Domain\Knowledge\KnowledgeType;
use DressnMore\Aos\Knowledge\Domain\Repository\KnowledgeDocumentRepositoryInterface;

final class InMemoryKnowledgeDocumentRepository implements KnowledgeDocumentRepositoryInterface
{
    /** @var array<string, KnowledgeDocument> */
    private array $items = [];

    public function save(KnowledgeDocument $document): void
    {
        $this->items[$document->id()->toString()] = $document;
    }

    public function find(KnowledgeId $id): ?KnowledgeDocument
    {
        return $this->items[$id->toString()] ?? null;
    }

    public function findForTenant(
        ?string $tenantId,
        array $types = [],
        array $statuses = [],
        ?CollectionId $collectionId = null,
        int $limit = 200,
        bool $includeGlobal = true,
    ): array {
        $typeValues = array_map(static fn (KnowledgeType $t): string => $t->value, $types);
        $statusValues = array_map(static fn (KnowledgeLifecycleStatus $s): string => $s->value, $statuses);
        $out = [];

        foreach ($this->items as $document) {
            $isGlobal = $document->tenantId() === null;
            $isTenantMatch = $tenantId !== null && $document->tenantId() === $tenantId;

            if (! $isTenantMatch && ! ($includeGlobal && $isGlobal)) {
                continue;
            }
            if ($types !== [] && ! in_array($document->type()->value, $typeValues, true)) {
                continue;
            }
            if ($statuses !== [] && ! in_array($document->status()->value, $statusValues, true)) {
                continue;
            }
            if ($collectionId !== null && $document->collectionId()->toString() !== $collectionId->toString()) {
                continue;
            }

            $out[] = $document;
            if (count($out) >= $limit) {
                break;
            }
        }

        return $out;
    }

    public function delete(KnowledgeId $id): void
    {
        unset($this->items[$id->toString()]);
    }
}
