<?php

declare(strict_types=1);

namespace DressnMore\Aos\Observability\Tracing;

use DressnMore\Aos\Observability\Contracts\TracerInterface;
use stdClass;

/**
 * No-op tracer for foundation boot.
 */
final class NullTracer implements TracerInterface
{
    public function startSpan(string $name, array $attributes = []): object
    {
        $span = new stdClass;
        $span->name = $name;
        $span->attributes = $attributes;

        return $span;
    }

    public function endSpan(object $span): void
    {
    }
}
