<?php

declare(strict_types=1);

namespace DressnMore\Aos\Knowledge\Domain\Index;

use DressnMore\Aos\Knowledge\Domain\Knowledge\KnowledgeDocument;
use DressnMore\Aos\Knowledge\Domain\Knowledge\KnowledgeId;

interface KnowledgeIndexInterface
{
    public function index(KnowledgeDocument $document): void;

    public function remove(KnowledgeId $id): void;

    /**
     * @return list<KnowledgeId>
     */
    public function lookup(?string $tenantId, bool $includeGlobal = true): array;
}
