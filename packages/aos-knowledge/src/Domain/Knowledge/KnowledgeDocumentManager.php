<?php

declare(strict_types=1);

namespace DressnMore\Aos\Knowledge\Domain\Knowledge;

use DressnMore\Aos\Knowledge\Domain\Index\KnowledgeIndexInterface;
use DressnMore\Aos\Knowledge\Domain\Publishing\KnowledgePublisher;
use DressnMore\Aos\Knowledge\Domain\Repository\KnowledgeDocumentRepositoryInterface;
use DressnMore\Aos\Knowledge\Domain\Validation\KnowledgeValidator;
use RuntimeException;

final class KnowledgeDocumentManager
{
    public function __construct(
        private readonly KnowledgeDocumentRepositoryInterface $documents,
        private readonly KnowledgeIndexInterface $index,
        private readonly KnowledgePublisher $publisher = new KnowledgePublisher(),
        private readonly KnowledgeValidator $validator = new KnowledgeValidator(),
    ) {}

    public function register(KnowledgeDocument $document): KnowledgeDocument
    {
        if (! $this->validator->isValid($document) && $document->status()->isRetrievable()) {
            throw new RuntimeException('Invalid published knowledge.');
        }
        $this->documents->save($document);
        $this->index->index($document);

        return $document;
    }

    public function update(KnowledgeDocument $document): KnowledgeDocument
    {
        $this->documents->save($document);
        $this->index->index($document);

        return $document;
    }

    public function publish(KnowledgeDocument $document): KnowledgeDocument
    {
        $published = $this->publisher->publish($document);
        $this->documents->save($published);
        $this->index->index($published);

        return $published;
    }

    public function archive(KnowledgeDocument $document): KnowledgeDocument
    {
        $archived = $this->publisher->archive($document);
        $this->documents->save($archived);
        $this->index->index($archived);

        return $archived;
    }

    public function find(KnowledgeId $id): ?KnowledgeDocument
    {
        return $this->documents->find($id);
    }
}
