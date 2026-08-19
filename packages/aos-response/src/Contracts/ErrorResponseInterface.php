<?php

declare(strict_types=1);

namespace DressnMore\Aos\Response\Contracts;

use DressnMore\Aos\Response\Domain\Aggregator\ToolOutcome;
use DressnMore\Aos\Response\Domain\Response\FinalAiResponse;
use DressnMore\Aos\Response\Domain\Response\ResponseContext;

interface ErrorResponseInterface
{
    public function fromOutcome(ResponseContext $context, ToolOutcome $outcome): FinalAiResponse;

    /**
     * @param list<ToolOutcome> $failed
     */
    public function fromFailures(ResponseContext $context, array $failed): FinalAiResponse;
}
