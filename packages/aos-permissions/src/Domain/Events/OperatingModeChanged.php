<?php

declare(strict_types=1);

namespace DressnMore\Aos\Permissions\Domain\Events;

final class OperatingModeChanged extends PermissionDomainEvent
{
    public function __construct(
        string $correlationId,
        public readonly string $from,
        public readonly string $to,
    ) {
        parent::__construct($correlationId);
    }
}
