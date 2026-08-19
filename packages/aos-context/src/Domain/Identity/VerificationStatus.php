<?php

declare(strict_types=1);

namespace DressnMore\Aos\Context\Domain\Identity;

enum VerificationStatus: string
{
    case Unverified = 'unverified';
    case PendingHuman = 'pending_human';
    case Verified = 'verified';
    case Rejected = 'rejected';
    case Conflict = 'conflict';
}
