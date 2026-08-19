<?php

declare(strict_types=1);

namespace DressnMore\Aos\Observability\Contracts;

/**
 * Distributed tracing port (no-op friendly).
 */
interface TracerInterface
{
    /**
     * @param  array<string, string>  $attributes
     * @return object opaque span handle
     */
    public function startSpan(string $name, array $attributes = []): object;

    public function endSpan(object $span): void;
}
