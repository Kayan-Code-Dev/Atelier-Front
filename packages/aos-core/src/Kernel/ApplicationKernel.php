<?php

declare(strict_types=1);

namespace DressnMore\Aos\Core\Kernel;

use DressnMore\Aos\Core\Configuration\Contracts\ConfigurationProviderInterface;
use DressnMore\Aos\Core\Health\Contracts\HealthCheckInterface;
use DressnMore\Aos\Core\Kernel\Contracts\KernelInterface;
use DressnMore\Aos\Core\Module\ModuleLoader;
use DressnMore\Aos\Core\Module\Contracts\ModuleRegistryInterface;
use RuntimeException;
use Throwable;

/**
 * Foundation application kernel.
 *
 * Boot lifecycle:
 * Boot → Load Config → Register Modules → Register Contracts → Register Providers
 * → Register Event Bus → Register Observability → Health Check → Ready
 *
 * No business logic, AI, channels, planner, knowledge, or tools are loaded here.
 */
final class ApplicationKernel implements KernelInterface
{
    private BootState $state = BootState::Idle;

    private bool $booted = false;

    /** @var list<HealthCheckInterface> */
    private array $healthChecks = [];

    public function __construct(
        private readonly ConfigurationProviderInterface $config,
        private readonly ModuleRegistryInterface $registry,
        private readonly ModuleLoader $moduleLoader,
    ) {}

    public function registerHealthCheck(HealthCheckInterface $check): void
    {
        $this->healthChecks[] = $check;
    }

    public function boot(): void
    {
        if ($this->booted) {
            return;
        }

        try {
            $this->transition(BootState::LoadConfig);
            $this->assertConfigLoaded();

            $this->transition(BootState::RegisterModules);
            $this->moduleLoader->registerEnabled();

            $this->transition(BootState::RegisterContracts);
            // Contracts are bound by service providers before boot; this stage is a lifecycle marker.

            $this->transition(BootState::RegisterProviders);
            // Laravel providers already registered; stage retained for explicit AOS lifecycle.

            $this->transition(BootState::RegisterEventBus);
            $this->assertOptionalBindingMarker('aos.events');

            $this->transition(BootState::RegisterObservability);
            $this->assertOptionalBindingMarker('aos.observability');

            $this->transition(BootState::HealthCheck);
            $this->runHealthChecks();

            $this->moduleLoader->bootEnabled();

            $this->transition(BootState::Ready);
            $this->booted = true;
        } catch (Throwable $e) {
            $this->state = BootState::Failed;
            throw $e;
        }
    }

    public function isReady(): bool
    {
        return $this->booted && $this->state === BootState::Ready;
    }

    public function state(): string
    {
        return $this->state->value;
    }

    public function version(): string
    {
        return $this->config->version();
    }

    private function transition(BootState $state): void
    {
        $this->state = $state;
    }

    private function assertConfigLoaded(): void
    {
        if ($this->config->platformName() === '') {
            throw new RuntimeException('AOS configuration is missing platform name.');
        }
    }

    private function assertOptionalBindingMarker(string $moduleName): void
    {
        if (! $this->config->isModuleEnabled($moduleName)) {
            return;
        }

        if (! $this->registry->has($moduleName)) {
            throw new RuntimeException(sprintf(
                'AOS module [%s] is enabled but not registered in the module registry.',
                $moduleName
            ));
        }
    }

    private function runHealthChecks(): void
    {
        if (! (bool) $this->config->get('health.enabled', true)) {
            return;
        }

        foreach ($this->healthChecks as $check) {
            $result = $check->check();
            if (! ($result['healthy'] ?? false)) {
                $message = (string) ($result['message'] ?? 'Health check failed.');
                if ((bool) $this->config->get('boot.fail_on_unhealthy', false)) {
                    throw new RuntimeException(sprintf('AOS health check [%s] failed: %s', $check->name(), $message));
                }
            }
        }
    }
}
