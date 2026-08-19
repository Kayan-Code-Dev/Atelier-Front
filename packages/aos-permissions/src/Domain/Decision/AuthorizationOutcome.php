<?php

declare(strict_types=1);

namespace DressnMore\Aos\Permissions\Domain\Decision;

/**
 * Final authorization decision outcomes.
 */
enum AuthorizationOutcome: string
{
    case Authorized = 'authorized';
    case Denied = 'denied';
    case ApprovalRequired = 'approval_required';
    case HumanEscalation = 'human_escalation';
    case RetryLater = 'retry_later';

    public function isAllowing(): bool
    {
        return $this === self::Authorized;
    }

    public function blocksExecution(): bool
    {
        return $this !== self::Authorized;
    }
}
