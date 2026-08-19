<?php

declare(strict_types=1);

namespace DressnMore\Aos\Tools\Domain\Pipeline\Stages;

use DressnMore\Aos\Tools\Domain\Pipeline\PipelineStageInterface;
use DressnMore\Aos\Tools\Domain\Pipeline\PipelineStageName;
use DressnMore\Aos\Tools\Domain\Pipeline\PipelineState;
use DressnMore\Aos\Tools\Domain\Resolver\ToolResolver;

final class ResolveStage implements PipelineStageInterface
{
    public function __construct(
        private readonly ToolResolver $resolver,
    ) {}

    public function name(): PipelineStageName
    {
        return PipelineStageName::Resolved;
    }

    public function process(PipelineState $state): void
    {
        if ($state->result() !== null) {
            return;
        }

        $resolved = $this->resolver->resolve($state->request()->toolIdentifier());
        $state->setResolved($resolved);
    }
}
