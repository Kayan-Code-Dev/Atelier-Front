<?php

declare(strict_types=1);

namespace DressnMore\Aos\Permissions\Domain\Events;

final class ApprovalRejected extends PermissionDomainEvent
{
    public function __construct(
        string $correlationId,
        public readonly string $approvalRequestId,
        public readonly string $decidedBy,
    ) {
        parent::__construct($correlationId);
    }
}
