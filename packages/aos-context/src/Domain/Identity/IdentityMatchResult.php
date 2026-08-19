<?php

declare(strict_types=1);

namespace DressnMore\Aos\Context\Domain\Identity;

/**
 * Outcome of attempting to resolve a channel identity to a unified customer.
 */
final class IdentityMatchResult
{
    /**
     * @param  list<CustomerId>  $candidateCustomerIds
     */
    public function __construct(
        private readonly MatchDecision $decision,
        private readonly ConfidenceScore $confidence,
        private readonly ?CustomerId $matchedCustomerId = null,
        private readonly array $candidateCustomerIds = [],
        private readonly string $reason = '',
    ) {}

    public static function exact(CustomerId $customerId): self
    {
        return new self(MatchDecision::ExactLink, ConfidenceScore::exact(), $customerId, [$customerId], 'existing channel link');
    }

    public static function high(CustomerId $customerId, ConfidenceScore $score, string $reason): self
    {
        return new self(MatchDecision::HighConfidenceMatch, $score, $customerId, [$customerId], $reason);
    }

    public static function low(CustomerId $customerId, ConfidenceScore $score, string $reason): self
    {
        return new self(MatchDecision::LowConfidenceMatch, $score, $customerId, [$customerId], $reason);
    }

    /**
     * @param  list<CustomerId>  $candidates
     */
    public static function conflict(array $candidates, ConfidenceScore $score, string $reason): self
    {
        return new self(MatchDecision::Conflict, $score, null, $candidates, $reason);
    }

    public static function none(string $reason = 'no candidates'): self
    {
        return new self(MatchDecision::NoMatch, ConfidenceScore::none(), null, [], $reason);
    }

    public static function requiresHuman(CustomerId $customerId, ConfidenceScore $score, string $reason): self
    {
        return new self(MatchDecision::RequiresHumanVerification, $score, $customerId, [$customerId], $reason);
    }

    public function decision(): MatchDecision
    {
        return $this->decision;
    }

    public function confidence(): ConfidenceScore
    {
        return $this->confidence;
    }

    public function matchedCustomerId(): ?CustomerId
    {
        return $this->matchedCustomerId;
    }

    /**
     * @return list<CustomerId>
     */
    public function candidateCustomerIds(): array
    {
        return $this->candidateCustomerIds;
    }

    public function reason(): string
    {
        return $this->reason;
    }

    public function isConflict(): bool
    {
        return $this->decision === MatchDecision::Conflict;
    }

    public function requiresHumanVerification(): bool
    {
        return $this->decision === MatchDecision::RequiresHumanVerification
            || $this->decision === MatchDecision::LowConfidenceMatch
            || $this->decision === MatchDecision::Conflict;
    }
}
