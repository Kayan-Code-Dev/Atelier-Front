<?php

declare(strict_types=1);

namespace DressnMore\Aos\Permissions\Application;

use DressnMore\Aos\Events\Contracts\EventBusInterface;
use DressnMore\Aos\Permissions\Domain\Approval\ApprovalEngine;
use DressnMore\Aos\Permissions\Domain\Approval\ApprovalRequestId;
use DressnMore\Aos\Permissions\Domain\Authorization\AuthorizationContext;
use DressnMore\Aos\Permissions\Domain\Authorization\AuthorizationPipeline;
use DressnMore\Aos\Permissions\Domain\Authorization\AuthorizationRequest;
use DressnMore\Aos\Permissions\Domain\Authorization\AuthorizationStage;
use DressnMore\Aos\Permissions\Domain\Decision\AuthorizationOutcome;
use DressnMore\Aos\Permissions\Domain\Decision\DecisionContext;
use DressnMore\Aos\Permissions\Domain\Events\ApprovalGranted;
use DressnMore\Aos\Permissions\Domain\Events\ApprovalRejected;
use DressnMore\Aos\Permissions\Domain\Events\ApprovalRequested;
use DressnMore\Aos\Permissions\Domain\Events\AuthorizationDenied;
use DressnMore\Aos\Permissions\Domain\Events\AuthorizationGranted;
use DressnMore\Aos\Permissions\Domain\Events\AuthorizationRequested;
use DressnMore\Aos\Permissions\Domain\Events\CapabilityResolved;
use DressnMore\Aos\Permissions\Domain\Events\PolicyEvaluated;
use DressnMore\Aos\Permissions\Domain\Events\RiskEvaluated;
use DressnMore\Aos\Permissions\Domain\Risk\RiskLevel;

/**
 * Authorization Manager — orchestrates the Authorization Pipeline and emits events.
 */
final class AuthorizationManager
{
    public function __construct(
        private readonly AuthorizationPipeline $pipeline,
        private readonly ApprovalEngine $approvalEngine,
        private readonly EventBusInterface $eventBus,
    ) {}

    public function authorize(AuthorizationRequest $request): DecisionContext
    {
        $this->eventBus->publish(new AuthorizationRequested(
            $request->correlationId(),
            $request->requestedCapability()->toString(),
            $request->operatingMode()->toString(),
        ));

        $context = new AuthorizationContext($request);
        $context->markStage(AuthorizationStage::Requested->value);
        $this->pipeline->process($context);

        $this->publishIntermediateEvents($context);

        $decision = $this->toDecisionContext($context);
        $this->publishDecisionEvents($decision, $context);

        return $decision;
    }

    public function grantApproval(ApprovalRequestId $id, string $decidedBy, string $reason = ''): DecisionContext
    {
        $approval = $this->approvalEngine->grant($id, $decidedBy, $reason);
        $this->eventBus->publish(new ApprovalGranted(
            $approval->correlationId(),
            $approval->id()->toString(),
            $decidedBy,
        ));

        return new DecisionContext(
            AuthorizationOutcome::Authorized,
            'approval granted by '.$decidedBy,
            $approval->riskLevel(),
            'n/a',
            $approval->capability()->toString(),
            $approval->correlationId(),
            $approval->id(),
        );
    }

    public function rejectApproval(ApprovalRequestId $id, string $decidedBy, string $reason = ''): DecisionContext
    {
        $approval = $this->approvalEngine->reject($id, $decidedBy, $reason);
        $this->eventBus->publish(new ApprovalRejected(
            $approval->correlationId(),
            $approval->id()->toString(),
            $decidedBy,
        ));

        return new DecisionContext(
            AuthorizationOutcome::Denied,
            'approval rejected by '.$decidedBy.($reason !== '' ? ': '.$reason : ''),
            $approval->riskLevel(),
            'n/a',
            $approval->capability()->toString(),
            $approval->correlationId(),
            $approval->id(),
        );
    }

    private function publishIntermediateEvents(AuthorizationContext $context): void
    {
        $request = $context->request();
        $this->eventBus->publish(new CapabilityResolved(
            $request->correlationId(),
            $request->requestedCapability()->toString(),
            $context->capabilityDefinition() !== null,
        ));

        if ($context->policyEvaluation() !== null) {
            $this->eventBus->publish(new PolicyEvaluated(
                $request->correlationId(),
                $context->policyEvaluation()->dominantEffect()?->value,
                count($context->policyEvaluation()->matched()),
            ));
        }

        if ($context->riskEvaluation() !== null) {
            $risk = $context->riskEvaluation();
            $this->eventBus->publish(new RiskEvaluated(
                $request->correlationId(),
                $risk->level()->value,
                $risk->requiresApproval(),
                $risk->requiresHuman(),
            ));
        }

        if ($context->approvalRequest() !== null) {
            $this->eventBus->publish(new ApprovalRequested(
                $request->correlationId(),
                $context->approvalRequest()->id()->toString(),
                $request->requestedCapability()->toString(),
            ));
        }
    }

    private function publishDecisionEvents(DecisionContext $decision, AuthorizationContext $context): void
    {
        if ($decision->outcome() === AuthorizationOutcome::Authorized) {
            $this->eventBus->publish(new AuthorizationGranted(
                $decision->correlationId(),
                $decision->capability(),
                $decision->reason(),
            ));

            return;
        }

        $this->eventBus->publish(new AuthorizationDenied(
            $decision->correlationId(),
            $decision->capability(),
            $decision->reason(),
            $decision->outcome()->value,
        ));
    }

    private function toDecisionContext(AuthorizationContext $context): DecisionContext
    {
        $outcome = $context->outcome() ?? AuthorizationOutcome::Denied;
        $matched = array_map(
            static fn ($p) => $p->id()->toString(),
            $context->policyEvaluation()?->matched() ?? []
        );

        return new DecisionContext(
            $outcome,
            $context->reason() !== '' ? $context->reason() : 'no reason',
            $context->riskEvaluation()?->level() ?? RiskLevel::Medium,
            ($context->resolvedMode() ?? $context->request()->operatingMode())->toString(),
            $context->request()->requestedCapability()->toString(),
            $context->request()->correlationId(),
            $context->approvalRequest()?->id(),
            $matched,
            $context->stages(),
        );
    }
}
