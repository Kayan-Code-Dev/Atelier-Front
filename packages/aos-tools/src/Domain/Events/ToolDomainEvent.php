<?php

declare(strict_types=1);

namespace DressnMore\Aos\Tools\Domain\Events;

use DressnMore\Aos\Events\AbstractEvent;
use DressnMore\Aos\Events\Markers\DomainEventMarker;
use DressnMore\Aos\Tools\Domain\Tool\ToolIdentifier;

abstract class ToolDomainEvent extends AbstractEvent implements DomainEventMarker
{
    public function __construct(
        public readonly ToolIdentifier $toolIdentifier,
    ) {
        parent::__construct();
    }
}
