<?php

declare(strict_types=1);

namespace DressnMore\Aos\Knowledge\Domain\Knowledge;

use DressnMore\Aos\Knowledge\Domain\Collection\KnowledgeCollection;
use DressnMore\Aos\Knowledge\Domain\Collection\KnowledgeCollectionManager;
use DressnMore\Aos\Knowledge\Domain\Source\KnowledgeSource;
use DressnMore\Aos\Knowledge\Domain\Source\KnowledgeSourceManager;

/**
 * Central registry façade for collections, sources, and documents.
 */
final class KnowledgeRegistry
{
    public function __construct(
        private readonly KnowledgeCollectionManager $collections,
        private readonly KnowledgeSourceManager $sources,
        private readonly KnowledgeDocumentManager $documents,
    ) {}

    public function registerCollection(KnowledgeCollection $collection): KnowledgeCollection
    {
        return $this->collections->register($collection);
    }

    public function registerSource(KnowledgeSource $source): KnowledgeSource
    {
        return $this->sources->register($source);
    }

    public function registerDocument(KnowledgeDocument $document): KnowledgeDocument
    {
        return $this->documents->register($document);
    }

    public function collections(): KnowledgeCollectionManager
    {
        return $this->collections;
    }

    public function sources(): KnowledgeSourceManager
    {
        return $this->sources;
    }

    public function documents(): KnowledgeDocumentManager
    {
        return $this->documents;
    }
}
