<?php

declare(strict_types=1);

namespace DressnMore\Aos\Core\Health\Contracts;

/**
 * A single foundation health check contribution.
 */
interface HealthCheckInterface
{
    /**
     * Stable check name.
     */
    public function name(): string;

    /**
     * @return array{healthy: bool, message: string, meta?: array<string, mixed>}
     */
    public function check(): array;
}
