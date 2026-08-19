<?php

declare(strict_types=1);

namespace DressnMore\Aos\Context\Domain\Customer;

use DressnMore\Aos\Context\Domain\Identity\CustomerId;
use DressnMore\Aos\Context\Domain\Identity\MatchDecision;
use DressnMore\Aos\Context\Domain\Identity\VerificationStatus;

/**
 * Customer slice for the Context Snapshot.
 */
final class CustomerContext
{
    public function __construct(
        private readonly ?CustomerId $customerId,
        private readonly ?string $summary = null,
        private readonly MatchDecision $matchDecision = MatchDecision::NoMatch,
        private readonly VerificationStatus $verificationStatus = VerificationStatus::Unverified,
        private readonly float $matchConfidence = 0.0,
    ) {}

    public static function unknown(): self
    {
        return new self(null);
    }

    public function customerId(): ?CustomerId
    {
        return $this->customerId;
    }

    public function summary(): ?string
    {
        return $this->summary;
    }

    public function matchDecision(): MatchDecision
    {
        return $this->matchDecision;
    }

    public function verificationStatus(): VerificationStatus
    {
        return $this->verificationStatus;
    }

    public function matchConfidence(): float
    {
        return $this->matchConfidence;
    }
}
