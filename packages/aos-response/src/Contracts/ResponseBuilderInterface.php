<?php

declare(strict_types=1);

namespace DressnMore\Aos\Response\Contracts;

use DressnMore\Aos\Response\Domain\Aggregator\AggregatedToolResults;
use DressnMore\Aos\Response\Domain\Response\FinalAiResponse;
use DressnMore\Aos\Response\Domain\Response\ResponseContext;

interface ResponseBuilderInterface
{
    public function build(ResponseContext $context, AggregatedToolResults $results): FinalAiResponse;
}
