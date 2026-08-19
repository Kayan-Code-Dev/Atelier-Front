<?php

declare(strict_types=1);

namespace DressnMore\Aos\Permissions\Domain\Events;

final class AuthorizationDenied extends PermissionDomainEvent
{
    public function __construct(
        string $correlationId,
        public readonly string $capability,
        public readonly string $reason,
        public readonly string $outcome,
    ) {
        parent::__construct($correlationId);
    }
}
