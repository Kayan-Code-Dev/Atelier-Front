<?php

declare(strict_types=1);

namespace DressnMore\Aos\Tools;

use DressnMore\Aos\Core\Module\Contracts\ModuleRegistryInterface;
use DressnMore\Aos\Tools\Application\ToolGateway;
use DressnMore\Aos\Tools\Application\ToolPipelineFactory;
use DressnMore\Aos\Tools\Domain\Contracts\ToolAnalyticsHookInterface;
use DressnMore\Aos\Tools\Domain\Contracts\ToolAuditHookInterface;
use DressnMore\Aos\Tools\Domain\Contracts\ToolAuthorizationHookInterface;
use DressnMore\Aos\Tools\Domain\Contracts\ToolExecutorInterface;
use DressnMore\Aos\Tools\Domain\Contracts\ToolValidatorInterface;
use DressnMore\Aos\Tools\Domain\Discovery\ToolDiscovery;
use DressnMore\Aos\Tools\Domain\Executor\ToolExecutor;
use DressnMore\Aos\Tools\Domain\Factories\ToolRequestFactory;
use DressnMore\Aos\Tools\Domain\Pipeline\ToolExecutionPipeline;
use DressnMore\Aos\Tools\Domain\Registry\ToolRegistry;
use DressnMore\Aos\Tools\Domain\Registry\ToolRegistryInterface;
use DressnMore\Aos\Tools\Domain\Resolver\ToolResolver;
use DressnMore\Aos\Tools\Domain\Validator\ConceptualToolValidator;
use DressnMore\Aos\Tools\Infrastructure\Authorization\CapabilityAuthorizationHook;
use DressnMore\Aos\Tools\Infrastructure\Hooks\InMemoryAnalyticsHook;
use DressnMore\Aos\Tools\Infrastructure\Hooks\InMemoryAuditHook;
use DressnMore\Aos\Tools\Module\ToolsModule;
use Illuminate\Support\ServiceProvider;

/**
 * Registers Business Tool Gateway contracts and the aos.tools module.
 */
final class AosToolsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ToolRegistry::class);
        $this->app->singleton(ToolRegistryInterface::class, ToolRegistry::class);
        $this->app->singleton(ToolDiscovery::class);
        $this->app->singleton(ToolResolver::class);
        $this->app->singleton(ToolRequestFactory::class);
        $this->app->singleton(ToolValidatorInterface::class, ConceptualToolValidator::class);
        $this->app->singleton(ToolAuthorizationHookInterface::class, CapabilityAuthorizationHook::class);
        $this->app->singleton(ToolExecutorInterface::class, ToolExecutor::class);
        $this->app->singleton(ToolAuditHookInterface::class, InMemoryAuditHook::class);
        $this->app->singleton(ToolAnalyticsHookInterface::class, InMemoryAnalyticsHook::class);
        $this->app->singleton(ToolPipelineFactory::class);
        $this->app->singleton(ToolExecutionPipeline::class, static function ($app): ToolExecutionPipeline {
            return $app->make(ToolPipelineFactory::class)->create();
        });
        $this->app->singleton(ToolGateway::class);
        $this->app->singleton(ToolsModule::class);

        $this->app->afterResolving(ModuleRegistryInterface::class, function (ModuleRegistryInterface $registry): void {
            $this->registerModule($registry);
        });

        if ($this->app->resolved(ModuleRegistryInterface::class)) {
            $this->registerModule($this->app->make(ModuleRegistryInterface::class));
        }
    }

    public function boot(): void
    {
        $this->registerModule($this->app->make(ModuleRegistryInterface::class));
    }

    private function registerModule(ModuleRegistryInterface $registry): void
    {
        if (! $registry->has('aos.tools')) {
            $registry->add($this->app->make(ToolsModule::class));
        }
    }
}
