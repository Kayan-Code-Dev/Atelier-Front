<?php

declare(strict_types=1);

namespace DressnMore\Aos\Response\Application;

use DressnMore\Aos\Response\Contracts\ResultAggregatorInterface;
use DressnMore\Aos\Response\Domain\Aggregator\AggregatedToolResults;
use DressnMore\Aos\Response\Domain\Aggregator\ToolOutcome;

final class ResultAggregator implements ResultAggregatorInterface
{
    public function aggregate(array $outcomes): AggregatedToolResults
    {
        usort($outcomes, static fn (ToolOutcome $a, ToolOutcome $b): int => $a->order() <=> $b->order());

        $succeeded = [];
        $failed = [];
        foreach ($outcomes as $outcome) {
            if ($outcome->success()) {
                $succeeded[] = $outcome;
            } else {
                $failed[] = $outcome;
            }
        }

        return new AggregatedToolResults($outcomes, $succeeded, $failed);
    }
}
