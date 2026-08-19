<?php

declare(strict_types=1);

namespace DressnMore\Aos\Tools\Domain\Pipeline\Stages;

use DressnMore\Aos\Tools\Domain\Contracts\ToolAuditHookInterface;
use DressnMore\Aos\Tools\Domain\Pipeline\PipelineStageInterface;
use DressnMore\Aos\Tools\Domain\Pipeline\PipelineStageName;
use DressnMore\Aos\Tools\Domain\Pipeline\PipelineState;

final class AuditStage implements PipelineStageInterface
{
    public function __construct(
        private readonly ToolAuditHookInterface $audit,
    ) {}

    public function name(): PipelineStageName
    {
        return PipelineStageName::Audit;
    }

    public function process(PipelineState $state): void
    {
        if ($state->result() === null || $state->manifest() === null) {
            return;
        }

        $ref = $this->audit->record($state->request(), $state->manifest(), $state->result());
        $state->setResult($state->result()->withReferences($ref, null));
    }
}
