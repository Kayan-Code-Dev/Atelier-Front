<?php

declare(strict_types=1);

namespace DressnMore\Aos\Tools\Domain\Pipeline\Stages;

use DressnMore\Aos\Tools\Domain\Pipeline\PipelineStageInterface;
use DressnMore\Aos\Tools\Domain\Pipeline\PipelineStageName;
use DressnMore\Aos\Tools\Domain\Pipeline\PipelineState;

final class MetadataStage implements PipelineStageInterface
{
    public function name(): PipelineStageName
    {
        return PipelineStageName::MetadataLoaded;
    }

    public function process(PipelineState $state): void
    {
        // Metadata already attached by ResolveStage via ResolvedTool.
        if ($state->result() !== null) {
            return;
        }
    }
}
