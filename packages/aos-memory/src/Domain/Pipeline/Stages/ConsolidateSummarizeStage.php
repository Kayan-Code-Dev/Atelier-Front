<?php

declare(strict_types=1);

namespace DressnMore\Aos\Memory\Domain\Pipeline\Stages;

use DressnMore\Aos\Memory\Domain\Memory\MemoryConsolidator;
use DressnMore\Aos\Memory\Domain\Memory\MemoryType;
use DressnMore\Aos\Memory\Domain\Pipeline\MemoryWriteBag;
use DressnMore\Aos\Memory\Domain\Pipeline\MemoryWriteStage;
use DressnMore\Aos\Memory\Domain\Pipeline\MemoryWriteStageInterface;
use DressnMore\Aos\Memory\Domain\Repository\MemoryStoreInterface;
use DressnMore\Aos\Memory\Domain\Summary\MemorySummarizer;
use DressnMore\Aos\Memory\Domain\Summary\SummaryKind;

final class ConsolidateSummarizeStage implements MemoryWriteStageInterface
{
    public function __construct(
        private readonly MemoryStoreInterface $store,
        private readonly MemoryConsolidator $consolidator,
        private readonly MemorySummarizer $summarizer,
    ) {}

    public function name(): MemoryWriteStage
    {
        return MemoryWriteStage::MemoryConsolidation;
    }

    public function process(MemoryWriteBag $bag): void
    {
        $update = $bag->update();
        $existing = $this->store->findByScope(
            $update->tenantId(),
            $update->customerId(),
            $update->conversationId(),
            [],
            200,
        );

        $bag->mark(MemoryWriteStage::DuplicateDetection->value);
        $consolidated = $this->consolidator->consolidate($bag->accepted(), $existing);
        $bag->setAccepted($consolidated);

        $forSummary = array_merge($existing, $consolidated);
        $summary = $this->summarizer->summarize(
            $update->tenantId(),
            $update->conversationId(),
            $update->customerId(),
            $forSummary,
            SummaryKind::Incremental,
        );
        $bag->setSummary($summary);
        $bag->mark(MemoryWriteStage::Summarization->value);

        // Persist a Summary memory fact (classified), not raw messages.
        if ($summary->facts() !== []) {
            $summaryFacts = array_filter(
                $consolidated,
                static fn ($r) => $r->type() === MemoryType::Summary
            );
            if ($summaryFacts === []) {
                // leave as summary artifact on bag; MemoryEngine may persist summary record via writer
            }
        }
    }
}
