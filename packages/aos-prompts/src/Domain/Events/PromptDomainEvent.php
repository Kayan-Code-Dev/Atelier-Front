<?php

declare(strict_types=1);

namespace DressnMore\Aos\Prompts\Domain\Events;

use DressnMore\Aos\Events\AbstractEvent;
use DressnMore\Aos\Events\Markers\DomainEventMarker;

abstract class PromptDomainEvent extends AbstractEvent implements DomainEventMarker
{
    public function __construct(
        public readonly string $correlationId,
    ) {
        parent::__construct();
    }
}
