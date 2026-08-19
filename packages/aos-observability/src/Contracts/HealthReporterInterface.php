<?php

declare(strict_types=1);

namespace DressnMore\Aos\Observability\Contracts;

/**
 * Aggregates health check results for reporting.
 */
interface HealthReporterInterface
{
    /**
     * @return array{healthy: bool, checks: array<string, array{healthy: bool, message: string, meta?: array<string, mixed>}>}
     */
    public function report(): array;
}
