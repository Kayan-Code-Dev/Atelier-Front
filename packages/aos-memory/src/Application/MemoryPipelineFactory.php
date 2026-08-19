<?php

declare(strict_types=1);

namespace DressnMore\Aos\Memory\Application;

use DressnMore\Aos\Memory\Domain\Context\MemoryContextBuilder;
use DressnMore\Aos\Memory\Domain\Factory\MemoryFactory;
use DressnMore\Aos\Memory\Domain\Index\MemoryIndexInterface;
use DressnMore\Aos\Memory\Domain\Memory\MemoryConsolidator;
use DressnMore\Aos\Memory\Domain\Memory\MemoryFactExtractor;
use DressnMore\Aos\Memory\Domain\Memory\MemoryRetriever;
use DressnMore\Aos\Memory\Domain\Memory\MemoryWriter;
use DressnMore\Aos\Memory\Domain\Pipeline\MemoryWritePipeline;
use DressnMore\Aos\Memory\Domain\Pipeline\Stages\ConsolidateSummarizeStage;
use DressnMore\Aos\Memory\Domain\Pipeline\Stages\ExtractAndClassifyStage;
use DressnMore\Aos\Memory\Domain\Pipeline\Stages\StoreAndIndexStage;
use DressnMore\Aos\Memory\Domain\Policies\MemoryPolicy;
use DressnMore\Aos\Memory\Domain\Ranking\MemoryRanker;
use DressnMore\Aos\Memory\Domain\Repository\MemoryStoreInterface;
use DressnMore\Aos\Memory\Domain\Retrieval\MemoryRetrievalPipeline;
use DressnMore\Aos\Memory\Domain\Retrieval\Stages\RankAndBuildContextStage;
use DressnMore\Aos\Memory\Domain\Retrieval\Stages\RetrieveScopedMemoriesStage;
use DressnMore\Aos\Memory\Domain\Summary\MemorySummarizer;

final class MemoryPipelineFactory
{
    public function __construct(
        private readonly MemoryStoreInterface $store,
        private readonly MemoryIndexInterface $index,
        private readonly MemoryFactory $factory,
        private readonly MemoryPolicy $policy,
        private readonly MemoryFactExtractor $extractor,
        private readonly MemoryConsolidator $consolidator,
        private readonly MemorySummarizer $summarizer,
        private readonly MemoryRanker $ranker,
        private readonly MemoryContextBuilder $contextBuilder,
    ) {}

    public function createWritePipeline(): MemoryWritePipeline
    {
        return new MemoryWritePipeline([
            new ExtractAndClassifyStage($this->extractor, $this->factory, $this->policy),
            new ConsolidateSummarizeStage($this->store, $this->consolidator, $this->summarizer),
            new StoreAndIndexStage($this->store, $this->index, $this->factory),
        ]);
    }

    public function createRetrievalPipeline(): MemoryRetrievalPipeline
    {
        return new MemoryRetrievalPipeline([
            new RetrieveScopedMemoriesStage($this->store),
            new RankAndBuildContextStage($this->ranker, $this->contextBuilder, $this->summarizer),
        ]);
    }

    public function createWriter(): MemoryWriter
    {
        return new MemoryWriter($this->createWritePipeline());
    }

    public function createRetriever(): MemoryRetriever
    {
        return new MemoryRetriever($this->createRetrievalPipeline());
    }
}
