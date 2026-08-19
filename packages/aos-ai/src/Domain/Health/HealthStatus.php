<?php

declare(strict_types=1);

namespace DressnMore\Aos\Ai\Domain\Health;

enum HealthStatus: string
{
    case Healthy = 'healthy';
    case Degraded = 'degraded';
    case Unhealthy = 'unhealthy';
    case Unknown = 'unknown';

    public function isUsable(): bool
    {
        return $this === self::Healthy || $this === self::Degraded;
    }
}
