<?php

declare(strict_types=1);

namespace DressnMore\Aos\Memory\Domain\Retrieval\Stages;

use DressnMore\Aos\Memory\Domain\Context\MemoryContextBuilder;
use DressnMore\Aos\Memory\Domain\Memory\MemoryRecord;
use DressnMore\Aos\Memory\Domain\Ranking\MemoryRanker;
use DressnMore\Aos\Memory\Domain\Retrieval\MemoryRetrievalBag;
use DressnMore\Aos\Memory\Domain\Retrieval\MemoryRetrievalStage;
use DressnMore\Aos\Memory\Domain\Retrieval\MemoryRetrievalStageInterface;
use DressnMore\Aos\Memory\Domain\Summary\MemorySummarizer;
use DressnMore\Aos\Memory\Domain\Summary\SummaryKind;

final class RankAndBuildContextStage implements MemoryRetrievalStageInterface
{
    public function __construct(
        private readonly MemoryRanker $ranker,
        private readonly MemoryContextBuilder $contextBuilder,
        private readonly MemorySummarizer $summarizer,
    ) {}

    public function name(): MemoryRetrievalStage
    {
        return MemoryRetrievalStage::MemoryContextReady;
    }

    public function process(MemoryRetrievalBag $bag): void
    {
        $req = $bag->request();
        $merged = $this->unique(array_merge(
            $bag->working(),
            $bag->conversation(),
            $bag->customer(),
            $bag->business(),
        ));

        $ranked = $this->ranker->rank($merged, $req->query());
        $ranked = array_slice($ranked, 0, $req->limit());
        $bag->setRanked($ranked);
        $bag->mark(MemoryRetrievalStage::RankMemories->value);

        $summary = null;
        if ($req->conversationId() !== null) {
            $summary = $this->summarizer->summarize(
                $req->tenantId(),
                $req->conversationId(),
                $req->customerId(),
                $ranked,
                SummaryKind::Rolling,
            );
            $bag->setSummary($summary);
        }

        $context = $this->contextBuilder->build(
            $req->tenantId(),
            $req->customerId(),
            $req->conversationId(),
            $ranked,
            $summary,
            true,
        );
        $bag->setContext($context);
        $bag->mark(MemoryRetrievalStage::CompressContext->value);
    }

    /**
     * @param  list<MemoryRecord>  $records
     * @return list<MemoryRecord>
     */
    private function unique(array $records): array
    {
        $seen = [];
        $out = [];
        foreach ($records as $record) {
            $id = $record->id()->toString();
            if (isset($seen[$id])) {
                continue;
            }
            $seen[$id] = true;
            $out[] = $record;
        }

        return $out;
    }
}
