<?php

declare(strict_types=1);

namespace DressnMore\Aos\Tools\Domain\Pipeline\Stages;

use DressnMore\Aos\Tools\Domain\Pipeline\PipelineStageInterface;
use DressnMore\Aos\Tools\Domain\Pipeline\PipelineStageName;
use DressnMore\Aos\Tools\Domain\Pipeline\PipelineState;

/**
 * Marks that execution context already exists on the immutable ToolRequest.
 */
final class ExecutionContextStage implements PipelineStageInterface
{
    public function name(): PipelineStageName
    {
        return PipelineStageName::ExecutionContextCreated;
    }

    public function process(PipelineState $state): void
    {
        // Context is supplied immutably on ToolRequest; stage is an explicit lifecycle marker.
    }
}
