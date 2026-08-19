<?php

declare(strict_types=1);

namespace DressnMore\Aos\Knowledge\Domain\Knowledge;

use DressnMore\Aos\Knowledge\Domain\Context\KnowledgeContext;
use DressnMore\Aos\Knowledge\Domain\Pipeline\KnowledgeRetrievalBag;
use DressnMore\Aos\Knowledge\Domain\Pipeline\KnowledgeRetrievalPipeline;
use RuntimeException;

final class KnowledgeRetriever
{
    public function __construct(
        private readonly KnowledgeRetrievalPipeline $pipeline,
    ) {}

    public function retrieve(KnowledgeRetrievalRequest $request): KnowledgeContext
    {
        $bag = $this->pipeline->process(new KnowledgeRetrievalBag($request));
        $context = $bag->context();
        if ($context === null) {
            throw new RuntimeException('Knowledge retrieval pipeline did not produce a context.');
        }

        return $context;
    }

    public function retrieveBag(KnowledgeRetrievalRequest $request): KnowledgeRetrievalBag
    {
        return $this->pipeline->process(new KnowledgeRetrievalBag($request));
    }
}
