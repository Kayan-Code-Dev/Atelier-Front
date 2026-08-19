<?php

declare(strict_types=1);

namespace DressnMore\Aos\Tools\Domain\Pipeline\Stages;

use DressnMore\Aos\Tools\Domain\Contracts\ToolExecutorInterface;
use DressnMore\Aos\Tools\Domain\Pipeline\PipelineStageInterface;
use DressnMore\Aos\Tools\Domain\Pipeline\PipelineStageName;
use DressnMore\Aos\Tools\Domain\Pipeline\PipelineState;
use DressnMore\Aos\Tools\Domain\Result\ExecutionStatus;
use DressnMore\Aos\Tools\Domain\Result\ToolFailure;
use DressnMore\Aos\Tools\Domain\Result\ToolResult;
use Throwable;

final class ExecutionStage implements PipelineStageInterface
{
    public function __construct(
        private readonly ToolExecutorInterface $executor,
    ) {}

    public function name(): PipelineStageName
    {
        return PipelineStageName::Execute;
    }

    public function process(PipelineState $state): void
    {
        if ($state->result() !== null || $state->manifest() === null || $state->metadata() === null) {
            return;
        }

        try {
            $result = $this->executor->execute(
                $state->request(),
                $state->manifest(),
                $state->metadata(),
            );
            $state->setResult($result);
        } catch (Throwable $e) {
            $state->setResult(ToolResult::failed(
                [ToolFailure::of('EXECUTION_ERROR', $e->getMessage(), false)],
                0.0,
                ExecutionStatus::Failed,
            ));
        }
    }
}
