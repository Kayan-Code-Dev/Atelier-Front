<?php

declare(strict_types=1);

namespace DressnMore\Aos\Knowledge\Domain\Source;

use DressnMore\Aos\Knowledge\Domain\Repository\KnowledgeSourceRepositoryInterface;

final class KnowledgeSourceManager
{
    public function __construct(
        private readonly KnowledgeSourceRepositoryInterface $sources,
    ) {}

    public function register(KnowledgeSource $source): KnowledgeSource
    {
        $this->sources->save($source);

        return $source;
    }

    public function get(SourceId $id): ?KnowledgeSource
    {
        return $this->sources->find($id);
    }

    /**
     * @return list<KnowledgeSource>
     */
    public function all(?string $tenantId = null): array
    {
        return $this->sources->all($tenantId);
    }
}
