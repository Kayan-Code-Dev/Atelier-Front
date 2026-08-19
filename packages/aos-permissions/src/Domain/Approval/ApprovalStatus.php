<?php

declare(strict_types=1);

namespace DressnMore\Aos\Permissions\Domain\Approval;

enum ApprovalStatus: string
{
    case Pending = 'pending';
    case Granted = 'granted';
    case Rejected = 'rejected';
    case Expired = 'expired';
    case TimedOut = 'timed_out';
    case Cancelled = 'cancelled';
}
