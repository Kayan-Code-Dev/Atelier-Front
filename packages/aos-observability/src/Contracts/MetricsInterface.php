<?php

declare(strict_types=1);

namespace DressnMore\Aos\Observability\Contracts;

/**
 * Metrics collection port.
 */
interface MetricsInterface
{
    /**
     * @param  array<string, string>  $tags
     */
    public function increment(string $name, float $value = 1.0, array $tags = []): void;

    /**
     * @param  array<string, string>  $tags
     */
    public function gauge(string $name, float $value, array $tags = []): void;

    /**
     * @param  array<string, string>  $tags
     */
    public function timing(string $name, float $milliseconds, array $tags = []): void;
}
