<?php

declare(strict_types=1);

namespace DressnMore\Aos\Events\Module;

use DressnMore\Aos\Core\Module\AbstractModule;
use DressnMore\Aos\Events\Bus\EventBus;
use DressnMore\Aos\Events\Bus\EventBusReady;
use DressnMore\Aos\Events\Contracts\EventBusInterface;

/**
 * Foundation events module.
 */
final class EventsModule extends AbstractModule
{
    public function __construct(
        private readonly EventBusInterface $eventBus,
    ) {}

    public function name(): string
    {
        return $this->assertName('aos.events');
    }

    public function title(): string
    {
        return 'AOS Events';
    }

    public function version(): string
    {
        return '0.1.0';
    }

    public function boot(): void
    {
        $this->eventBus->publish(new EventBusReady($this->version()));
    }

    public function isHealthy(): bool
    {
        return $this->eventBus instanceof EventBus;
    }
}
