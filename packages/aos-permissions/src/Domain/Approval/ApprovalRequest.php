<?php

declare(strict_types=1);

namespace DressnMore\Aos\Permissions\Domain\Approval;

use DateInterval;
use DateTimeImmutable;
use DressnMore\Aos\Permissions\Domain\Capability\CapabilityCode;
use DressnMore\Aos\Permissions\Domain\Risk\RiskLevel;

/**
 * Approval request aggregate for gated authorization outcomes.
 */
final class ApprovalRequest
{
    private ApprovalStatus $status = ApprovalStatus::Pending;

    private ?ApprovalDecision $decision = null;

    private readonly DateTimeImmutable $createdAt;

    private DateTimeImmutable $expiresAt;

    /**
     * @param  list<string>  $chain  ordered approver roles/ids
     */
    public function __construct(
        private readonly ApprovalRequestId $id,
        private readonly string $correlationId,
        private readonly CapabilityCode $capability,
        private readonly RiskLevel $riskLevel,
        private readonly array $chain,
        private readonly DateInterval $timeout,
        ?DateTimeImmutable $createdAt = null,
    ) {
        $this->createdAt = $createdAt ?? new DateTimeImmutable();
        $this->expiresAt = $this->createdAt->add($timeout);
    }

    /**
     * @param  list<string>  $chain
     */
    public static function open(
        string $correlationId,
        CapabilityCode $capability,
        RiskLevel $riskLevel,
        array $chain = ['human_supervisor'],
        ?DateInterval $timeout = null,
    ): self {
        return new self(
            ApprovalRequestId::generate(),
            $correlationId,
            $capability,
            $riskLevel,
            $chain,
            $timeout ?? new DateInterval('PT1H'),
        );
    }

    public function id(): ApprovalRequestId
    {
        return $this->id;
    }

    public function correlationId(): string
    {
        return $this->correlationId;
    }

    public function capability(): CapabilityCode
    {
        return $this->capability;
    }

    public function riskLevel(): RiskLevel
    {
        return $this->riskLevel;
    }

    /**
     * @return list<string>
     */
    public function chain(): array
    {
        return $this->chain;
    }

    public function status(): ApprovalStatus
    {
        return $this->status;
    }

    public function decision(): ?ApprovalDecision
    {
        return $this->decision;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function expiresAt(): DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function timeout(): DateInterval
    {
        return $this->timeout;
    }

    public function isExpired(?DateTimeImmutable $now = null): bool
    {
        return ($now ?? new DateTimeImmutable()) > $this->expiresAt;
    }

    public function grant(string $decidedBy, string $reason = ''): void
    {
        $this->assertPending();
        $this->status = ApprovalStatus::Granted;
        $this->decision = ApprovalDecision::grant($decidedBy, $reason);
    }

    public function reject(string $decidedBy, string $reason = ''): void
    {
        $this->assertPending();
        $this->status = ApprovalStatus::Rejected;
        $this->decision = ApprovalDecision::reject($decidedBy, $reason);
    }

    public function markExpired(): void
    {
        if ($this->status !== ApprovalStatus::Pending) {
            return;
        }
        $this->status = ApprovalStatus::Expired;
    }

    public function markTimedOut(): void
    {
        if ($this->status !== ApprovalStatus::Pending) {
            return;
        }
        $this->status = ApprovalStatus::TimedOut;
    }

    private function assertPending(): void
    {
        if ($this->status !== ApprovalStatus::Pending) {
            throw new \DomainException('Approval request is no longer pending.');
        }
        if ($this->isExpired()) {
            $this->status = ApprovalStatus::Expired;
            throw new \DomainException('Approval request has expired.');
        }
    }
}
