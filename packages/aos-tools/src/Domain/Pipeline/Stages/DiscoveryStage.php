<?php

declare(strict_types=1);

namespace DressnMore\Aos\Tools\Domain\Pipeline\Stages;

use DressnMore\Aos\Tools\Domain\Discovery\ToolDiscovery;
use DressnMore\Aos\Tools\Domain\Pipeline\PipelineStageInterface;
use DressnMore\Aos\Tools\Domain\Pipeline\PipelineStageName;
use DressnMore\Aos\Tools\Domain\Pipeline\PipelineState;
use DressnMore\Aos\Tools\Domain\Result\ExecutionStatus;
use DressnMore\Aos\Tools\Domain\Result\ToolFailure;
use DressnMore\Aos\Tools\Domain\Result\ToolResult;

final class DiscoveryStage implements PipelineStageInterface
{
    public function __construct(
        private readonly ToolDiscovery $discovery,
    ) {}

    public function name(): PipelineStageName
    {
        return PipelineStageName::Discovered;
    }

    public function process(PipelineState $state): void
    {
        if ($state->result() !== null) {
            return;
        }

        $id = $state->request()->toolIdentifier();
        if (! $this->discovery->exists($id)) {
            $state->setResult(ToolResult::failed(
                [ToolFailure::of('TOOL_NOT_FOUND', sprintf('Unknown tool [%s].', $id->toString()))],
                0.0,
                ExecutionStatus::NotFound,
            ));
        }
    }
}
