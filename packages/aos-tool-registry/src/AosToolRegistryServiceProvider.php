<?php

declare(strict_types=1);

namespace DressnMore\Aos\ToolRegistry;

use DressnMore\Aos\Core\Module\Contracts\ModuleRegistryInterface;
use DressnMore\Aos\ToolRegistry\Application\ApprovalRegistry;
use DressnMore\Aos\ToolRegistry\Application\CapabilityRegistry;
use DressnMore\Aos\ToolRegistry\Application\CapabilityResolver;
use DressnMore\Aos\ToolRegistry\Application\CapabilityValidator;
use DressnMore\Aos\ToolRegistry\Application\IntentRegistry;
use DressnMore\Aos\ToolRegistry\Application\IntentResolver;
use DressnMore\Aos\ToolRegistry\Application\PolicyRegistry;
use DressnMore\Aos\ToolRegistry\Application\ProviderRegistry;
use DressnMore\Aos\ToolRegistry\Application\RegistryBootstrapper;
use DressnMore\Aos\ToolRegistry\Application\RegistryExporter;
use DressnMore\Aos\ToolRegistry\Application\RegistrySnapshotBuilder;
use DressnMore\Aos\ToolRegistry\Application\ToolAvailabilityManager;
use DressnMore\Aos\ToolRegistry\Application\ToolCatalog;
use DressnMore\Aos\ToolRegistry\Application\ToolDiscovery;
use DressnMore\Aos\ToolRegistry\Application\ToolHealthRegistry;
use DressnMore\Aos\ToolRegistry\Application\ToolLoader;
use DressnMore\Aos\ToolRegistry\Application\ToolMetadataRegistry;
use DressnMore\Aos\ToolRegistry\Application\ToolRegistrar;
use DressnMore\Aos\ToolRegistry\Application\ToolRegistry;
use DressnMore\Aos\ToolRegistry\Application\ToolResolver;
use DressnMore\Aos\ToolRegistry\Application\ToolValidator;
use DressnMore\Aos\ToolRegistry\Contracts\ApprovalRegistryInterface;
use DressnMore\Aos\ToolRegistry\Contracts\CapabilityRegistryInterface;
use DressnMore\Aos\ToolRegistry\Contracts\IntentRegistryInterface;
use DressnMore\Aos\ToolRegistry\Contracts\PolicyRegistryInterface;
use DressnMore\Aos\ToolRegistry\Contracts\ProviderRegistryInterface;
use DressnMore\Aos\ToolRegistry\Contracts\RegistryEventPublisherInterface;
use DressnMore\Aos\ToolRegistry\Contracts\RegistryExporterInterface;
use DressnMore\Aos\ToolRegistry\Contracts\RegistrySnapshotBuilderInterface;
use DressnMore\Aos\ToolRegistry\Contracts\ToolDiscoveryInterface;
use DressnMore\Aos\ToolRegistry\Contracts\ToolMetadataRegistryInterface;
use DressnMore\Aos\ToolRegistry\Contracts\ToolRegistryInterface;
use DressnMore\Aos\ToolRegistry\Contracts\ToolResolverInterface;
use DressnMore\Aos\ToolRegistry\Infrastructure\InMemory\InMemoryRegistryEventPublisher;
use DressnMore\Aos\ToolRegistry\Module\ToolRegistryModule;
use Illuminate\Support\ServiceProvider;

final class AosToolRegistryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ToolRegistry::class);
        $this->app->singleton(ToolRegistryInterface::class, ToolRegistry::class);
        $this->app->singleton(ToolMetadataRegistry::class);
        $this->app->singleton(ToolMetadataRegistryInterface::class, ToolMetadataRegistry::class);
        $this->app->singleton(CapabilityRegistry::class);
        $this->app->singleton(CapabilityRegistryInterface::class, CapabilityRegistry::class);
        $this->app->singleton(IntentRegistry::class);
        $this->app->singleton(IntentRegistryInterface::class, IntentRegistry::class);
        $this->app->singleton(PolicyRegistry::class);
        $this->app->singleton(PolicyRegistryInterface::class, PolicyRegistry::class);
        $this->app->singleton(ApprovalRegistry::class);
        $this->app->singleton(ApprovalRegistryInterface::class, ApprovalRegistry::class);
        $this->app->singleton(ProviderRegistry::class);
        $this->app->singleton(ProviderRegistryInterface::class, ProviderRegistry::class);
        $this->app->singleton(RegistryEventPublisherInterface::class, InMemoryRegistryEventPublisher::class);
        $this->app->singleton(ToolCatalog::class);
        $this->app->singleton(ToolDiscovery::class);
        $this->app->singleton(ToolDiscoveryInterface::class, ToolDiscovery::class);
        $this->app->singleton(ToolResolver::class);
        $this->app->singleton(ToolResolverInterface::class, ToolResolver::class);
        $this->app->singleton(ToolValidator::class);
        $this->app->singleton(ToolLoader::class);
        $this->app->singleton(ToolAvailabilityManager::class);
        $this->app->singleton(ToolHealthRegistry::class);
        $this->app->singleton(CapabilityResolver::class);
        $this->app->singleton(CapabilityValidator::class);
        $this->app->singleton(IntentResolver::class);
        $this->app->singleton(ToolRegistrar::class);
        $this->app->singleton(RegistryBootstrapper::class);
        $this->app->singleton(RegistrySnapshotBuilder::class);
        $this->app->singleton(RegistrySnapshotBuilderInterface::class, RegistrySnapshotBuilder::class);
        $this->app->singleton(RegistryExporter::class);
        $this->app->singleton(RegistryExporterInterface::class, RegistryExporter::class);
        $this->app->singleton(ToolRegistryModule::class);
    }

    public function boot(): void
    {
        /** @var ModuleRegistryInterface $registry */
        $registry = $this->app->make(ModuleRegistryInterface::class);
        if (! $registry->has('aos.tool-registry')) {
            $registry->add($this->app->make(ToolRegistryModule::class));
        }
    }
}
