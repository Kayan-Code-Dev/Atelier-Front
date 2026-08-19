<?php

declare(strict_types=1);

namespace DressnMore\Aos\Knowledge\Domain\Search;

use DressnMore\Aos\Knowledge\Domain\Knowledge\KnowledgeDocument;

final class KnowledgeSearchHit
{
    public function __construct(
        private readonly KnowledgeDocument $document,
        private readonly float $relevance,
    ) {}

    public function document(): KnowledgeDocument
    {
        return $this->document;
    }

    public function relevance(): float
    {
        return $this->relevance;
    }
}
