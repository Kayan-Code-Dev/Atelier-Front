<?php

declare(strict_types=1);

namespace DressnMore\Aos\Permissions\Domain\Events;

final class ApprovalRequested extends PermissionDomainEvent
{
    public function __construct(
        string $correlationId,
        public readonly string $approvalRequestId,
        public readonly string $capability,
    ) {
        parent::__construct($correlationId);
    }
}
