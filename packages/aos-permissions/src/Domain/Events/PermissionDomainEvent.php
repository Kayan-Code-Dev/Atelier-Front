<?php

declare(strict_types=1);

namespace DressnMore\Aos\Permissions\Domain\Events;

use DressnMore\Aos\Events\AbstractEvent;
use DressnMore\Aos\Events\Markers\DomainEventMarker;

abstract class PermissionDomainEvent extends AbstractEvent implements DomainEventMarker
{
    public function __construct(
        public readonly string $correlationId,
    ) {
        parent::__construct();
    }
}
