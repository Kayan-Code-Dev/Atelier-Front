<?php

declare(strict_types=1);

namespace DressnMore\Aos\Events\Contracts;

/**
 * Dispatches events to listeners.
 */
interface DispatcherInterface
{
    public function dispatch(object $event): void;
}
