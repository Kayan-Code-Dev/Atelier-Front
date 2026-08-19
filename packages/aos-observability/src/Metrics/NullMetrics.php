<?php

declare(strict_types=1);

namespace DressnMore\Aos\Observability\Metrics;

use DressnMore\Aos\Observability\Contracts\MetricsInterface;

/**
 * No-op metrics sink for foundation boot (real backends later).
 */
final class NullMetrics implements MetricsInterface
{
    public function increment(string $name, float $value = 1.0, array $tags = []): void
    {
    }

    public function gauge(string $name, float $value, array $tags = []): void
    {
    }

    public function timing(string $name, float $milliseconds, array $tags = []): void
    {
    }
}
