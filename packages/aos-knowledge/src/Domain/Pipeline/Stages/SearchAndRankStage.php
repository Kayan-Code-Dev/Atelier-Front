<?php

declare(strict_types=1);

namespace DressnMore\Aos\Knowledge\Domain\Pipeline\Stages;

use DressnMore\Aos\Knowledge\Domain\Knowledge\KnowledgeLifecycleStatus;
use DressnMore\Aos\Knowledge\Domain\Pipeline\KnowledgeRetrievalBag;
use DressnMore\Aos\Knowledge\Domain\Pipeline\KnowledgeRetrievalStage;
use DressnMore\Aos\Knowledge\Domain\Pipeline\KnowledgeRetrievalStageInterface;
use DressnMore\Aos\Knowledge\Domain\Ranking\KnowledgeRanker;
use DressnMore\Aos\Knowledge\Domain\Repository\KnowledgeDocumentRepositoryInterface;
use DressnMore\Aos\Knowledge\Domain\Search\KnowledgeSearchEngineInterface;
use DressnMore\Aos\Knowledge\Domain\Search\KnowledgeSearchHit;

final class SearchAndRankStage implements KnowledgeRetrievalStageInterface
{
    public function __construct(
        private readonly KnowledgeDocumentRepositoryInterface $documents,
        private readonly KnowledgeSearchEngineInterface $search,
        private readonly KnowledgeRanker $ranker,
    ) {}

    public function name(): KnowledgeRetrievalStage
    {
        return KnowledgeRetrievalStage::CandidateRanking;
    }

    public function process(KnowledgeRetrievalBag $bag): void
    {
        $req = $bag->request();
        $corpus = $this->documents->findForTenant(
            $req->tenantId(),
            $req->types(),
            [KnowledgeLifecycleStatus::Published],
            null,
            500,
            $req->includeGlobal(),
        );

        if ($req->language() !== '') {
            $corpus = array_values(array_filter(
                $corpus,
                static fn ($d) => $d->language() === $req->language()
            ));
        }
        if ($req->tags() !== []) {
            $corpus = array_values(array_filter(
                $corpus,
                static function ($d) use ($req): bool {
                    foreach ($req->tags() as $tag) {
                        if (in_array($tag, $d->tags(), true)) {
                            return true;
                        }
                    }

                    return false;
                }
            ));
        }

        $bag->setCorpus($corpus);
        $hits = $this->search->search($req->query(), $corpus, max(50, $req->limit() * 3));
        $bag->setHits($hits);
        $bag->mark(KnowledgeRetrievalStage::KnowledgeSearch->value);

        $ranked = $this->ranker->rank($hits);
        $bag->setRanked($ranked);
    }
}
