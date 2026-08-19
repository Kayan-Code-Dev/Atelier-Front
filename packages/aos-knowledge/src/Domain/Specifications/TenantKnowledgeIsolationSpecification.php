<?php

declare(strict_types=1);

namespace DressnMore\Aos\Knowledge\Domain\Specifications;

use DressnMore\Aos\Knowledge\Domain\Knowledge\KnowledgeDocument;

final class TenantKnowledgeIsolationSpecification
{
    public function isSatisfiedBy(KnowledgeDocument $document, ?string $tenantId): bool
    {
        if ($document->tenantId() === null) {
            return true; // global / platform
        }

        return $tenantId !== null && $document->tenantId() === $tenantId;
    }
}
