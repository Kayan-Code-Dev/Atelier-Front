<?php

declare(strict_types=1);

namespace DressnMore\Aos\Knowledge\Domain\Knowledge;

use DressnMore\Aos\Knowledge\Domain\Context\KnowledgeContext;
use DressnMore\Aos\Knowledge\Domain\Repository\KnowledgeDocumentRepositoryInterface;
use DressnMore\Aos\Knowledge\Domain\Snapshot\KnowledgeSnapshot;

final class KnowledgeManager
{
    public function __construct(
        private readonly KnowledgeRegistry $registry,
        private readonly KnowledgeRetriever $retriever,
        private readonly KnowledgeDocumentRepositoryInterface $documents,
    ) {}

    public function register(KnowledgeDocument $document): KnowledgeDocument
    {
        return $this->registry->registerDocument($document);
    }

    public function publish(KnowledgeDocument $document): KnowledgeDocument
    {
        return $this->registry->documents()->publish($document);
    }

    public function archive(KnowledgeDocument $document): KnowledgeDocument
    {
        return $this->registry->documents()->archive($document);
    }

    public function update(KnowledgeDocument $document): KnowledgeDocument
    {
        return $this->registry->documents()->update($document);
    }

    public function recall(KnowledgeRetrievalRequest $request): KnowledgeContext
    {
        return $this->retriever->retrieve($request);
    }

    public function snapshot(?string $tenantId, int $limit = 50): KnowledgeSnapshot
    {
        $docs = $this->documents->findForTenant($tenantId, [], [], null, $limit, true);
        $digest = hash('sha256', implode('|', array_map(
            static fn (KnowledgeDocument $d): string => $d->id()->toString().':'.$d->version()->version(),
            $docs
        )));

        return new KnowledgeSnapshot($tenantId, $docs, $digest);
    }

    public function registry(): KnowledgeRegistry
    {
        return $this->registry;
    }

    public function retriever(): KnowledgeRetriever
    {
        return $this->retriever;
    }
}
