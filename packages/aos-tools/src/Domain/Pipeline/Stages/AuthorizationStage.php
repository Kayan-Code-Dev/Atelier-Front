<?php

declare(strict_types=1);

namespace DressnMore\Aos\Tools\Domain\Pipeline\Stages;

use DressnMore\Aos\Tools\Domain\Contracts\AuthorizationDecision;
use DressnMore\Aos\Tools\Domain\Contracts\ToolAuthorizationHookInterface;
use DressnMore\Aos\Tools\Domain\Pipeline\PipelineStageInterface;
use DressnMore\Aos\Tools\Domain\Pipeline\PipelineStageName;
use DressnMore\Aos\Tools\Domain\Pipeline\PipelineState;
use DressnMore\Aos\Tools\Domain\Result\ExecutionStatus;
use DressnMore\Aos\Tools\Domain\Result\ToolFailure;
use DressnMore\Aos\Tools\Domain\Result\ToolResult;

final class AuthorizationStage implements PipelineStageInterface
{
    public function __construct(
        private readonly ToolAuthorizationHookInterface $authorization,
    ) {}

    public function name(): PipelineStageName
    {
        return PipelineStageName::Authorization;
    }

    public function process(PipelineState $state): void
    {
        if ($state->result() !== null || $state->manifest() === null) {
            return;
        }

        $decision = $this->authorization->authorize($state->request(), $state->manifest());

        if ($decision === AuthorizationDecision::Deny) {
            $state->setResult(ToolResult::failed(
                [ToolFailure::of('AUTHORIZATION_DENIED', 'Tool authorization denied.')],
                0.0,
                ExecutionStatus::Denied,
            ));

            return;
        }

        if ($decision === AuthorizationDecision::RequireApproval) {
            $state->setResult(ToolResult::failed(
                [ToolFailure::of('AUTHORIZATION_APPROVAL', 'Tool requires human approval.')],
                0.0,
                ExecutionStatus::PendingApproval,
            ));
        }
    }
}
