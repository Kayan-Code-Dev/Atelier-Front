<?php

declare(strict_types=1);

namespace DressnMore\Aos\Events\Contracts;

/**
 * Marker-capable AOS event bus.
 */
interface EventBusInterface
{
    /**
     * Publish an event instance to all listeners/subscribers.
     */
    public function publish(object $event): void;

    /**
     * Subscribe a listener class or callable to an event class.
     *
     * @param  class-string  $event
     * @param  class-string|callable  $listener
     */
    public function subscribe(string $event, string|callable $listener): void;
}
