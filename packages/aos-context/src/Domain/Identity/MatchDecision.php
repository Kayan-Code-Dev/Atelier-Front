<?php

declare(strict_types=1);

namespace DressnMore\Aos\Context\Domain\Identity;

enum MatchDecision: string
{
    case ExactLink = 'exact_link';
    case HighConfidenceMatch = 'high_confidence_match';
    case LowConfidenceMatch = 'low_confidence_match';
    case Conflict = 'conflict';
    case NoMatch = 'no_match';
    case RequiresHumanVerification = 'requires_human_verification';
}
