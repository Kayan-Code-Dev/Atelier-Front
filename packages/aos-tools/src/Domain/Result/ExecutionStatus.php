<?php

declare(strict_types=1);

namespace DressnMore\Aos\Tools\Domain\Result;

enum ExecutionStatus: string
{
    case Success = 'success';
    case Failed = 'failed';
    case Denied = 'denied';
    case ValidationFailed = 'validation_failed';
    case NotFound = 'not_found';
    case PendingApproval = 'pending_approval';
}
