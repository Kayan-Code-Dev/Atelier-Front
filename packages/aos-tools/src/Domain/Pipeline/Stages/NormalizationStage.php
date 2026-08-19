<?php

declare(strict_types=1);

namespace DressnMore\Aos\Tools\Domain\Pipeline\Stages;

use DressnMore\Aos\Tools\Domain\Pipeline\PipelineStageInterface;
use DressnMore\Aos\Tools\Domain\Pipeline\PipelineStageName;
use DressnMore\Aos\Tools\Domain\Pipeline\PipelineState;
use DressnMore\Aos\Tools\Domain\Result\ToolResult;

final class NormalizationStage implements PipelineStageInterface
{
    public function name(): PipelineStageName
    {
        return PipelineStageName::Normalize;
    }

    public function process(PipelineState $state): void
    {
        $result = $state->result();
        if ($result === null) {
            $state->setResult(ToolResult::failed([]));
        }
    }
}
