<?php

declare(strict_types=1);

namespace DressnMore\Aos\Ai\Domain\Pipeline;

interface AiPipelineStageInterface
{
    public function name(): AiPipelineStage;

    public function process(AiPipelineBag $bag): void;
}
