<?php

declare(strict_types=1);

namespace DressnMore\SmartAssistant\Domain\Events;

use DressnMore\Aos\Events\AbstractEvent;
use DressnMore\Aos\Events\Markers\DomainEventMarker;

abstract class SmartAssistantDomainEvent extends AbstractEvent implements DomainEventMarker
{
    public function __construct(
        public readonly string $tenantId,
        public readonly string $correlationId = '',
    ) {
        parent::__construct();
    }
}
