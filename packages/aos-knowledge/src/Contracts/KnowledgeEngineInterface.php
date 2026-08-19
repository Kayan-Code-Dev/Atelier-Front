<?php

declare(strict_types=1);

namespace DressnMore\Aos\Knowledge\Contracts;

use DressnMore\Aos\Knowledge\Domain\Context\KnowledgeContext;
use DressnMore\Aos\Knowledge\Domain\Knowledge\KnowledgeDocument;
use DressnMore\Aos\Knowledge\Domain\Knowledge\KnowledgeRetrievalRequest;
use DressnMore\Aos\Knowledge\Domain\Snapshot\KnowledgeSnapshot;

interface KnowledgeEngineInterface
{
    public function register(KnowledgeDocument $document): KnowledgeDocument;

    public function publish(KnowledgeDocument $document): KnowledgeDocument;

    public function archive(KnowledgeDocument $document): KnowledgeDocument;

    public function update(KnowledgeDocument $document): KnowledgeDocument;

    public function retrieve(KnowledgeRetrievalRequest $request): KnowledgeContext;

    public function snapshot(?string $tenantId, int $limit = 50): KnowledgeSnapshot;
}
