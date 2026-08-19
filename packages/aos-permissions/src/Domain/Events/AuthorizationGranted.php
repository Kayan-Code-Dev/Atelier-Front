<?php

declare(strict_types=1);

namespace DressnMore\Aos\Permissions\Domain\Events;

final class AuthorizationGranted extends PermissionDomainEvent
{
    public function __construct(
        string $correlationId,
        public readonly string $capability,
        public readonly string $reason,
    ) {
        parent::__construct($correlationId);
    }
}
