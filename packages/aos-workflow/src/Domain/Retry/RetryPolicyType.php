<?php

declare(strict_types=1);

namespace DressnMore\Aos\Workflow\Domain\Retry;

enum RetryPolicyType: string
{
    case Immediate = 'immediate';
    case ExponentialBackoff = 'exponential_backoff';
    case ManualRetry = 'manual_retry';
    case DeadLetter = 'dead_letter';
}
