<?php

declare(strict_types=1);

namespace DressnMore\Aos\Tools\Domain\Pipeline\Stages;

use DressnMore\Aos\Tools\Domain\Contracts\ToolAnalyticsHookInterface;
use DressnMore\Aos\Tools\Domain\Pipeline\PipelineStageInterface;
use DressnMore\Aos\Tools\Domain\Pipeline\PipelineStageName;
use DressnMore\Aos\Tools\Domain\Pipeline\PipelineState;

final class AnalyticsStage implements PipelineStageInterface
{
    public function __construct(
        private readonly ToolAnalyticsHookInterface $analytics,
    ) {}

    public function name(): PipelineStageName
    {
        return PipelineStageName::Analytics;
    }

    public function process(PipelineState $state): void
    {
        if ($state->result() === null || $state->manifest() === null) {
            return;
        }

        $ref = $this->analytics->record($state->request(), $state->manifest(), $state->result());
        $state->setResult($state->result()->withReferences(null, $ref));
    }
}
