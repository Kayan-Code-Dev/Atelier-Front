<?php

declare(strict_types=1);

namespace DressnMore\Aos\Events;

/**
 * Base event with occurrence timestamp.
 */
abstract class AbstractEvent
{
    public readonly float $occurredAt;

    public function __construct()
    {
        $this->occurredAt = microtime(true);
    }
}
