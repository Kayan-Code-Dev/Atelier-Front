<?php

declare(strict_types=1);

namespace DressnMore\Aos\Knowledge\Domain\Repository;

use DressnMore\Aos\Knowledge\Domain\Source\KnowledgeSource;
use DressnMore\Aos\Knowledge\Domain\Source\SourceId;

interface KnowledgeSourceRepositoryInterface
{
    public function save(KnowledgeSource $source): void;

    public function find(SourceId $id): ?KnowledgeSource;

    /**
     * @return list<KnowledgeSource>
     */
    public function all(?string $tenantId = null): array;
}
