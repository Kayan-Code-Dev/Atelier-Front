<?php

declare(strict_types=1);

namespace DressnMore\Aos\Response\Contracts;

use DressnMore\Aos\Response\Domain\Aggregator\AggregatedToolResults;
use DressnMore\Aos\Response\Domain\Aggregator\ToolOutcome;

interface ResultAggregatorInterface
{
    /**
     * @param list<ToolOutcome> $outcomes
     */
    public function aggregate(array $outcomes): AggregatedToolResults;
}
