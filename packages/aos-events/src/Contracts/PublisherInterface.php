<?php

declare(strict_types=1);

namespace DressnMore\Aos\Events\Contracts;

/**
 * Publishes events onto the bus.
 */
interface PublisherInterface
{
    public function publish(object $event): void;
}
