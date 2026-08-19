<?php

declare(strict_types=1);

namespace DressnMore\Aos\Permissions;

use DressnMore\Aos\Core\Module\Contracts\ModuleRegistryInterface;
use DressnMore\Aos\Permissions\Application\AuthorizationManager;
use DressnMore\Aos\Permissions\Application\AuthorizationPipelineFactory;
use DressnMore\Aos\Permissions\Application\PermissionEngineFacade;
use DressnMore\Aos\Permissions\Domain\Approval\ApprovalEngine;
use DressnMore\Aos\Permissions\Domain\Approval\ApprovalRepositoryInterface;
use DressnMore\Aos\Permissions\Domain\Authorization\AuthorizationPipeline;
use DressnMore\Aos\Permissions\Domain\Capability\CapabilityEngine;
use DressnMore\Aos\Permissions\Domain\Capability\CapabilityRegistry;
use DressnMore\Aos\Permissions\Domain\Capability\CapabilityRegistryInterface;
use DressnMore\Aos\Permissions\Domain\Decision\DecisionEngine;
use DressnMore\Aos\Permissions\Domain\Factories\AuthorizationRequestFactory;
use DressnMore\Aos\Permissions\Domain\Mode\OperatingModeManager;
use DressnMore\Aos\Permissions\Domain\Permission\PermissionEngine;
use DressnMore\Aos\Permissions\Domain\Permission\PermissionRegistry;
use DressnMore\Aos\Permissions\Domain\Permission\PermissionRegistryInterface;
use DressnMore\Aos\Permissions\Domain\Policy\PolicyEngine;
use DressnMore\Aos\Permissions\Domain\Policy\PolicyRegistry;
use DressnMore\Aos\Permissions\Domain\Policy\PolicyRegistryInterface;
use DressnMore\Aos\Permissions\Domain\Policy\PolicyResolver;
use DressnMore\Aos\Permissions\Domain\Risk\RiskEvaluator;
use DressnMore\Aos\Permissions\Infrastructure\Bootstrap\BuiltinCatalogBootstrap;
use DressnMore\Aos\Permissions\Infrastructure\Persistence\InMemoryApprovalRepository;
use DressnMore\Aos\Permissions\Module\PermissionsModule;
use Illuminate\Support\ServiceProvider;

/**
 * Registers Permission & Policy Engine contracts and the aos.permissions module.
 */
final class AosPermissionsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(CapabilityRegistry::class);
        $this->app->singleton(CapabilityRegistryInterface::class, CapabilityRegistry::class);
        $this->app->singleton(PermissionRegistry::class);
        $this->app->singleton(PermissionRegistryInterface::class, PermissionRegistry::class);
        $this->app->singleton(PolicyRegistry::class);
        $this->app->singleton(PolicyRegistryInterface::class, PolicyRegistry::class);
        $this->app->singleton(InMemoryApprovalRepository::class);
        $this->app->singleton(ApprovalRepositoryInterface::class, InMemoryApprovalRepository::class);

        $this->app->singleton(OperatingModeManager::class);
        $this->app->singleton(CapabilityEngine::class);
        $this->app->singleton(PermissionEngine::class);
        $this->app->singleton(PolicyEngine::class);
        $this->app->singleton(PolicyResolver::class);
        $this->app->singleton(RiskEvaluator::class);
        $this->app->singleton(DecisionEngine::class);
        $this->app->singleton(ApprovalEngine::class);
        $this->app->singleton(AuthorizationRequestFactory::class);
        $this->app->singleton(BuiltinCatalogBootstrap::class);
        $this->app->singleton(AuthorizationPipelineFactory::class);
        $this->app->singleton(AuthorizationPipeline::class, static function ($app): AuthorizationPipeline {
            return $app->make(AuthorizationPipelineFactory::class)->create();
        });
        $this->app->singleton(AuthorizationManager::class);
        $this->app->singleton(PermissionEngineFacade::class);
        $this->app->singleton(PermissionsModule::class);

        $this->app->afterResolving(ModuleRegistryInterface::class, function (ModuleRegistryInterface $registry): void {
            $this->registerModule($registry);
        });

        if ($this->app->resolved(ModuleRegistryInterface::class)) {
            $this->registerModule($this->app->make(ModuleRegistryInterface::class));
        }
    }

    public function boot(): void
    {
        $this->app->make(BuiltinCatalogBootstrap::class)->seed();
        $this->registerModule($this->app->make(ModuleRegistryInterface::class));
    }

    private function registerModule(ModuleRegistryInterface $registry): void
    {
        if (! $registry->has('aos.permissions')) {
            $registry->add($this->app->make(PermissionsModule::class));
        }
    }
}
