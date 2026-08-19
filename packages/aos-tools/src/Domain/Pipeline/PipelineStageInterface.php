<?php

declare(strict_types=1);

namespace DressnMore\Aos\Tools\Domain\Pipeline;

interface PipelineStageInterface
{
    public function name(): PipelineStageName;

    public function process(PipelineState $state): void;
}
