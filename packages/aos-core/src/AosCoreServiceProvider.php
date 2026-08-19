<?php

declare(strict_types=1);

namespace DressnMore\Aos\Core;

use DressnMore\Aos\Core\Configuration\AosConfigurationProvider;
use DressnMore\Aos\Core\Configuration\Contracts\ConfigurationProviderInterface;
use DressnMore\Aos\Core\Health\Contracts\HealthCheckInterface;
use DressnMore\Aos\Core\Health\KernelHealthCheck;
use DressnMore\Aos\Core\Kernel\ApplicationKernel;
use DressnMore\Aos\Core\Kernel\Contracts\KernelInterface;
use DressnMore\Aos\Core\Module\CoreModule;
use DressnMore\Aos\Core\Module\Contracts\ModuleRegistryInterface;
use DressnMore\Aos\Core\Module\ModuleLoader;
use DressnMore\Aos\Core\Module\ModuleRegistry;
use DressnMore\Aos\Core\Version\Contracts\VersionProviderInterface;
use DressnMore\Aos\Core\Version\VersionProvider;
use Illuminate\Support\ServiceProvider;

/**
 * Registers AOS core kernel, configuration, modules, and health checks.
 */
final class AosCoreServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/aos.php', 'aos');

        $this->app->singleton(ConfigurationProviderInterface::class, AosConfigurationProvider::class);
        $this->app->singleton(ModuleRegistryInterface::class, ModuleRegistry::class);
        $this->app->singleton(ModuleLoader::class);
        $this->app->singleton(VersionProviderInterface::class, VersionProvider::class);
        $this->app->singleton(CoreModule::class);

        $this->app->singleton(KernelInterface::class, ApplicationKernel::class);

        $this->app->singleton(KernelHealthCheck::class);
        $this->app->tag([KernelHealthCheck::class], 'aos.health_checks');

        /** @var ModuleRegistryInterface $registry */
        $registry = $this->app->make(ModuleRegistryInterface::class);
        $registry->add($this->app->make(CoreModule::class));
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/aos.php' => config_path('aos.php'),
            ], 'aos-config');
        }

        // Boot after every package provider has registered its module.
        $this->app->booted(function (): void {
            /** @var ApplicationKernel $kernel */
            $kernel = $this->app->make(KernelInterface::class);

            foreach ($this->app->tagged('aos.health_checks') as $check) {
                if ($check instanceof HealthCheckInterface) {
                    $kernel->registerHealthCheck($check);
                }
            }

            $kernel->boot();
        });
    }
}
