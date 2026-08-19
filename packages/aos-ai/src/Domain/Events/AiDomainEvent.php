<?php

declare(strict_types=1);

namespace DressnMore\Aos\Ai\Domain\Events;

use DressnMore\Aos\Events\AbstractEvent;
use DressnMore\Aos\Events\Markers\DomainEventMarker;

abstract class AiDomainEvent extends AbstractEvent implements DomainEventMarker
{
    public function __construct(
        public readonly string $correlationId,
    ) {
        parent::__construct();
    }
}
