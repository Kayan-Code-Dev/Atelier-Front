<?php

declare(strict_types=1);

namespace DressnMore\Aos\Knowledge\Infrastructure\Persistence;

use DressnMore\Aos\Knowledge\Domain\Index\KnowledgeIndexInterface;
use DressnMore\Aos\Knowledge\Domain\Knowledge\KnowledgeDocument;
use DressnMore\Aos\Knowledge\Domain\Knowledge\KnowledgeId;

final class InMemoryKnowledgeIndex implements KnowledgeIndexInterface
{
    /** @var array<string, ?string> id => tenantId */
    private array $entries = [];

    public function index(KnowledgeDocument $document): void
    {
        $this->entries[$document->id()->toString()] = $document->tenantId();
    }

    public function remove(KnowledgeId $id): void
    {
        unset($this->entries[$id->toString()]);
    }

    public function lookup(?string $tenantId, bool $includeGlobal = true): array
    {
        $ids = [];
        foreach ($this->entries as $id => $docTenant) {
            if ($docTenant === null && $includeGlobal) {
                $ids[] = KnowledgeId::fromString($id);
                continue;
            }
            if ($tenantId !== null && $docTenant === $tenantId) {
                $ids[] = KnowledgeId::fromString($id);
            }
        }

        return $ids;
    }
}
