<?php

declare(strict_types=1);

namespace DressnMore\Aos\Events\Bus;

use DressnMore\Aos\Events\AbstractEvent;
use DressnMore\Aos\Events\Markers\InfrastructureEventMarker;

/**
 * Emitted when the AOS event bus module becomes ready (foundation only).
 */
final class EventBusReady extends AbstractEvent implements InfrastructureEventMarker
{
    public function __construct(
        public readonly string $moduleVersion,
    ) {
        parent::__construct();
    }
}
