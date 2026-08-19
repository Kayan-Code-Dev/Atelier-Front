<?php

declare(strict_types=1);

namespace DressnMore\Aos\Context\Domain\Identity;

/**
 * Confidence thresholds for identity matching — never auto-link on weak evidence.
 */
final class ConfidenceRules
{
    public function __construct(
        private readonly float $autoLinkThreshold = 0.95,
        private readonly float $suggestThreshold = 0.70,
        private readonly float $humanReviewThreshold = 0.50,
    ) {}

    public function decide(ConfidenceScore $score, int $candidateCount): MatchDecision
    {
        if ($candidateCount === 0) {
            return MatchDecision::NoMatch;
        }

        if ($candidateCount > 1 && $score->isAtLeast($this->suggestThreshold)) {
            return MatchDecision::Conflict;
        }

        if ($score->isAtLeast($this->autoLinkThreshold)) {
            return MatchDecision::HighConfidenceMatch;
        }

        if ($score->isAtLeast($this->suggestThreshold)) {
            return MatchDecision::RequiresHumanVerification;
        }

        if ($score->isAtLeast($this->humanReviewThreshold)) {
            return MatchDecision::LowConfidenceMatch;
        }

        return MatchDecision::NoMatch;
    }

    public function autoLinkThreshold(): float
    {
        return $this->autoLinkThreshold;
    }

    public function suggestThreshold(): float
    {
        return $this->suggestThreshold;
    }

    public function humanReviewThreshold(): float
    {
        return $this->humanReviewThreshold;
    }
}
