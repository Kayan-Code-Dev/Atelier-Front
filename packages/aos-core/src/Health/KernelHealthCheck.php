<?php

declare(strict_types=1);

namespace DressnMore\Aos\Core\Health;

use DressnMore\Aos\Core\Configuration\Contracts\ConfigurationProviderInterface;
use DressnMore\Aos\Core\Health\Contracts\HealthCheckInterface;
use DressnMore\Aos\Core\Module\ModuleLoader;

/**
 * Verifies enabled foundation modules report healthy.
 */
final class KernelHealthCheck implements HealthCheckInterface
{
    public function __construct(
        private readonly ModuleLoader $moduleLoader,
        private readonly ConfigurationProviderInterface $config,
    ) {}

    public function name(): string
    {
        return 'aos.kernel';
    }

    public function check(): array
    {
        $moduleStatuses = [];
        $healthy = true;

        foreach ($this->moduleLoader->enabledModules() as $module) {
            $ok = $module->isHealthy();
            $moduleStatuses[$module->name()] = $ok;
            $healthy = $healthy && $ok;
        }

        return [
            'healthy' => $healthy,
            'message' => $healthy
                ? 'Enabled AOS modules are healthy.'
                : 'One or more enabled AOS modules are unhealthy.',
            'meta' => [
                'platform' => $this->config->platformName(),
                'version' => $this->config->version(),
                'modules' => $moduleStatuses,
            ],
        ];
    }
}
