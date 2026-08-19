<?php

declare(strict_types=1);

namespace DressnMore\Aos\Events\Contracts;

/**
 * Contract for typed event listeners.
 */
interface EventListenerInterface
{
    public function handle(object $event): void;
}
