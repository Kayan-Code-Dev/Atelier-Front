<?php

declare(strict_types=1);

namespace DressnMore\Aos\Permissions\Domain\Events;

final class AuthorizationRequested extends PermissionDomainEvent
{
    public function __construct(
        string $correlationId,
        public readonly string $capability,
        public readonly string $operatingMode,
    ) {
        parent::__construct($correlationId);
    }
}
