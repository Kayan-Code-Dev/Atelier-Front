<?php

declare(strict_types=1);

namespace DressnMore\Aos\Tools\Domain\Pipeline\Stages;

use DressnMore\Aos\Tools\Domain\Contracts\ToolValidatorInterface;
use DressnMore\Aos\Tools\Domain\Pipeline\PipelineStageInterface;
use DressnMore\Aos\Tools\Domain\Pipeline\PipelineStageName;
use DressnMore\Aos\Tools\Domain\Pipeline\PipelineState;
use DressnMore\Aos\Tools\Domain\Result\ExecutionStatus;
use DressnMore\Aos\Tools\Domain\Result\ToolResult;

final class ValidationStage implements PipelineStageInterface
{
    public function __construct(
        private readonly ToolValidatorInterface $validator,
    ) {}

    public function name(): PipelineStageName
    {
        return PipelineStageName::InputValidated;
    }

    public function process(PipelineState $state): void
    {
        if ($state->result() !== null || $state->manifest() === null) {
            return;
        }

        $failures = $this->validator->validate($state->request(), $state->manifest());
        if ($failures !== []) {
            $state->setResult(ToolResult::failed($failures, 0.0, ExecutionStatus::ValidationFailed));
        }
    }
}
