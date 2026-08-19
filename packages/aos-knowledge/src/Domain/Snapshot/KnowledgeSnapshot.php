<?php

declare(strict_types=1);

namespace DressnMore\Aos\Knowledge\Domain\Snapshot;

use DateTimeImmutable;
use DressnMore\Aos\Knowledge\Domain\Knowledge\KnowledgeDocument;

final class KnowledgeSnapshot
{
    /**
     * @param  list<KnowledgeDocument>  $documents
     */
    public function __construct(
        private readonly ?string $tenantId,
        private readonly array $documents,
        private readonly string $digest,
        private readonly DateTimeImmutable $generatedAt = new DateTimeImmutable(),
    ) {}

    public function tenantId(): ?string
    {
        return $this->tenantId;
    }

    /** @return list<KnowledgeDocument> */
    public function documents(): array
    {
        return $this->documents;
    }

    public function digest(): string
    {
        return $this->digest;
    }

    public function generatedAt(): DateTimeImmutable
    {
        return $this->generatedAt;
    }
}
