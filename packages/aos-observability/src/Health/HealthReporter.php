<?php

declare(strict_types=1);

namespace DressnMore\Aos\Observability\Health;

use DressnMore\Aos\Core\Health\Contracts\HealthCheckInterface;
use DressnMore\Aos\Observability\Contracts\HealthReporterInterface;
use Illuminate\Contracts\Container\Container;

/**
 * Reports tagged AOS health checks.
 */
final class HealthReporter implements HealthReporterInterface
{
    public function __construct(
        private readonly Container $container,
    ) {}

    public function report(): array
    {
        $checks = [];
        $healthy = true;

        foreach ($this->container->tagged('aos.health_checks') as $check) {
            if (! $check instanceof HealthCheckInterface) {
                continue;
            }

            $result = $check->check();
            $checks[$check->name()] = $result;
            $healthy = $healthy && ($result['healthy'] ?? false);
        }

        return [
            'healthy' => $healthy,
            'checks' => $checks,
        ];
    }
}
