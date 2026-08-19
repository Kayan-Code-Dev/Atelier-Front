<?php

declare(strict_types=1);

namespace DressnMore\Aos\Permissions\Domain\Decision;

use DressnMore\Aos\Permissions\Domain\Approval\ApprovalRequestId;
use DressnMore\Aos\Permissions\Domain\Risk\RiskLevel;

/**
 * Immutable final decision context returned by the Permission Engine.
 */
final class DecisionContext
{
    /**
     * @param  list<string>  $matchedPolicyIds
     * @param  list<string>  $stages
     */
    public function __construct(
        private readonly AuthorizationOutcome $outcome,
        private readonly string $reason,
        private readonly RiskLevel $riskLevel,
        private readonly string $operatingMode,
        private readonly string $capability,
        private readonly string $correlationId,
        private readonly ?ApprovalRequestId $approvalRequestId = null,
        private readonly array $matchedPolicyIds = [],
        private readonly array $stages = [],
    ) {}

    public function outcome(): AuthorizationOutcome
    {
        return $this->outcome;
    }

    public function reason(): string
    {
        return $this->reason;
    }

    public function riskLevel(): RiskLevel
    {
        return $this->riskLevel;
    }

    public function operatingMode(): string
    {
        return $this->operatingMode;
    }

    public function capability(): string
    {
        return $this->capability;
    }

    public function correlationId(): string
    {
        return $this->correlationId;
    }

    public function approvalRequestId(): ?ApprovalRequestId
    {
        return $this->approvalRequestId;
    }

    /**
     * @return list<string>
     */
    public function matchedPolicyIds(): array
    {
        return $this->matchedPolicyIds;
    }

    /**
     * @return list<string>
     */
    public function stages(): array
    {
        return $this->stages;
    }

    public function isAuthorized(): bool
    {
        return $this->outcome === AuthorizationOutcome::Authorized;
    }
}
