<?php

declare(strict_types=1);

namespace DressnMore\Aos\Knowledge\Domain\Specifications;

use DressnMore\Aos\Knowledge\Domain\Knowledge\KnowledgeDocument;

final class PublishedKnowledgeSpecification
{
    public function isSatisfiedBy(KnowledgeDocument $document): bool
    {
        return $document->isPublished();
    }
}
