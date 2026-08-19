<?php

declare(strict_types=1);

namespace DressnMore\Aos\Events\Bus;

use DressnMore\Aos\Events\Contracts\DispatcherInterface;
use Illuminate\Contracts\Events\Dispatcher;

/**
 * Dispatcher adapter over Laravel's event dispatcher.
 */
final class IlluminateEventDispatcher implements DispatcherInterface
{
    public function __construct(
        private readonly Dispatcher $dispatcher,
    ) {}

    public function dispatch(object $event): void
    {
        $this->dispatcher->dispatch($event);
    }
}
