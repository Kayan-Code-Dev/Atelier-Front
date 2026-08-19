<?php

declare(strict_types=1);

namespace DressnMore\Aos\Knowledge\Application;

use DressnMore\Aos\Knowledge\Domain\Collection\KnowledgeCollectionManager;
use DressnMore\Aos\Knowledge\Domain\Context\KnowledgeContextBuilder;
use DressnMore\Aos\Knowledge\Domain\Factory\KnowledgeFactory;
use DressnMore\Aos\Knowledge\Domain\Index\KnowledgeIndexInterface;
use DressnMore\Aos\Knowledge\Domain\Knowledge\KnowledgeDocumentManager;
use DressnMore\Aos\Knowledge\Domain\Knowledge\KnowledgeRegistry;
use DressnMore\Aos\Knowledge\Domain\Knowledge\KnowledgeRetriever;
use DressnMore\Aos\Knowledge\Domain\Pipeline\KnowledgeRetrievalPipeline;
use DressnMore\Aos\Knowledge\Domain\Pipeline\Stages\PolicyFilterAndContextStage;
use DressnMore\Aos\Knowledge\Domain\Pipeline\Stages\SearchAndRankStage;
use DressnMore\Aos\Knowledge\Domain\Policies\KnowledgePolicyEngine;
use DressnMore\Aos\Knowledge\Domain\Publishing\KnowledgePublisher;
use DressnMore\Aos\Knowledge\Domain\Ranking\KnowledgeRanker;
use DressnMore\Aos\Knowledge\Domain\Repository\KnowledgeDocumentRepositoryInterface;
use DressnMore\Aos\Knowledge\Domain\Search\KnowledgeSearchEngineInterface;
use DressnMore\Aos\Knowledge\Domain\Source\KnowledgeSourceManager;
use DressnMore\Aos\Knowledge\Domain\Validation\KnowledgeValidator;

final class KnowledgePipelineFactory
{
    public function __construct(
        private readonly KnowledgeDocumentRepositoryInterface $documents,
        private readonly KnowledgeSearchEngineInterface $search,
        private readonly KnowledgeRanker $ranker,
        private readonly KnowledgePolicyEngine $policies,
        private readonly KnowledgeContextBuilder $contextBuilder,
    ) {}

    public function createRetrievalPipeline(): KnowledgeRetrievalPipeline
    {
        return new KnowledgeRetrievalPipeline([
            new SearchAndRankStage($this->documents, $this->search, $this->ranker),
            new PolicyFilterAndContextStage($this->policies, $this->contextBuilder),
        ]);
    }

    public function createRetriever(): KnowledgeRetriever
    {
        return new KnowledgeRetriever($this->createRetrievalPipeline());
    }

    public function createRegistry(
        KnowledgeCollectionManager $collections,
        KnowledgeSourceManager $sources,
        KnowledgeIndexInterface $index,
    ): KnowledgeRegistry {
        return new KnowledgeRegistry(
            $collections,
            $sources,
            new KnowledgeDocumentManager(
                $this->documents,
                $index,
                new KnowledgePublisher(new KnowledgeValidator(), $this->policies),
                new KnowledgeValidator(),
            ),
        );
    }
}
