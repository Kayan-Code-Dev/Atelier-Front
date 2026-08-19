<?php

declare(strict_types=1);

namespace DressnMore\Aos\Memory\Domain\Memory;

use DressnMore\Aos\Memory\Domain\Context\MemoryContext;
use DressnMore\Aos\Memory\Domain\Retrieval\MemoryRetrievalBag;
use DressnMore\Aos\Memory\Domain\Retrieval\MemoryRetrievalPipeline;
use RuntimeException;

/**
 * Retrieves ranked memory context through the retrieval pipeline.
 */
final class MemoryRetriever
{
    public function __construct(
        private readonly MemoryRetrievalPipeline $pipeline,
    ) {}

    public function retrieve(MemoryRetrievalRequest $request): MemoryContext
    {
        $bag = $this->pipeline->process(new MemoryRetrievalBag($request));
        $context = $bag->context();
        if ($context === null) {
            throw new RuntimeException('Memory retrieval pipeline did not produce a context.');
        }

        return $context;
    }

    public function retrieveBag(MemoryRetrievalRequest $request): MemoryRetrievalBag
    {
        return $this->pipeline->process(new MemoryRetrievalBag($request));
    }
}
