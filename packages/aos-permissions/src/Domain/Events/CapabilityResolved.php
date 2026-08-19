<?php

declare(strict_types=1);

namespace DressnMore\Aos\Permissions\Domain\Events;

final class CapabilityResolved extends PermissionDomainEvent
{
    public function __construct(
        string $correlationId,
        public readonly string $capability,
        public readonly bool $found,
    ) {
        parent::__construct($correlationId);
    }
}
