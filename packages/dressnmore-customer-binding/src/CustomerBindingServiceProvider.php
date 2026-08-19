<?php

declare(strict_types=1);

namespace DressnMore\CustomerBinding;

use DressnMore\Aos\Core\Module\Contracts\ModuleRegistryInterface;
use DressnMore\CustomerBinding\Application\CustomerCapabilityProvider;
use DressnMore\CustomerBinding\Application\CustomerContextBuilder;
use DressnMore\CustomerBinding\Application\CustomerIntentMapper;
use DressnMore\CustomerBinding\Application\CustomerPolicyAdapter;
use DressnMore\CustomerBinding\Application\CustomerResolver;
use DressnMore\CustomerBinding\Application\CustomerSnapshotBuilder;
use DressnMore\CustomerBinding\Application\CustomerTimelineBuilder;
use DressnMore\CustomerBinding\Application\CustomerToolAdapter;
use DressnMore\CustomerBinding\Contracts\CustomerCapabilityProviderInterface;
use DressnMore\CustomerBinding\Contracts\CustomerContextBuilderInterface;
use DressnMore\CustomerBinding\Contracts\CustomerEventPublisherInterface;
use DressnMore\CustomerBinding\Contracts\CustomerIntentMapperInterface;
use DressnMore\CustomerBinding\Contracts\CustomerPolicyAdapterInterface;
use DressnMore\CustomerBinding\Contracts\CustomerReadModelPortInterface;
use DressnMore\CustomerBinding\Contracts\CustomerResolverInterface;
use DressnMore\CustomerBinding\Contracts\CustomerSnapshotBuilderInterface;
use DressnMore\CustomerBinding\Contracts\CustomerTimelineBuilderInterface;
use DressnMore\CustomerBinding\Contracts\CustomerToolAdapterInterface;
use DressnMore\CustomerBinding\Infrastructure\InMemory\InMemoryCustomerEventPublisher;
use DressnMore\CustomerBinding\Infrastructure\InMemory\InMemoryCustomerReadModelPort;
use DressnMore\CustomerBinding\Module\CustomerBindingModule;
use Illuminate\Support\ServiceProvider;

final class CustomerBindingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(InMemoryCustomerReadModelPort::class);
        $this->app->singleton(CustomerReadModelPortInterface::class, InMemoryCustomerReadModelPort::class);
        $this->app->singleton(CustomerEventPublisherInterface::class, InMemoryCustomerEventPublisher::class);
        $this->app->singleton(CustomerToolAdapter::class);
        $this->app->singleton(CustomerToolAdapterInterface::class, CustomerToolAdapter::class);
        $this->app->singleton(CustomerCapabilityProvider::class);
        $this->app->singleton(CustomerCapabilityProviderInterface::class, CustomerCapabilityProvider::class);
        $this->app->singleton(CustomerIntentMapper::class);
        $this->app->singleton(CustomerIntentMapperInterface::class, CustomerIntentMapper::class);
        $this->app->singleton(CustomerPolicyAdapter::class);
        $this->app->singleton(CustomerPolicyAdapterInterface::class, CustomerPolicyAdapter::class);
        $this->app->singleton(CustomerContextBuilder::class);
        $this->app->singleton(CustomerContextBuilderInterface::class, CustomerContextBuilder::class);
        $this->app->singleton(CustomerSnapshotBuilder::class);
        $this->app->singleton(CustomerSnapshotBuilderInterface::class, CustomerSnapshotBuilder::class);
        $this->app->singleton(CustomerTimelineBuilder::class);
        $this->app->singleton(CustomerTimelineBuilderInterface::class, CustomerTimelineBuilder::class);
        $this->app->singleton(CustomerResolver::class);
        $this->app->singleton(CustomerResolverInterface::class, CustomerResolver::class);
        $this->app->singleton(CustomerBindingModule::class);
    }

    public function boot(): void
    {
        /** @var ModuleRegistryInterface $registry */
        $registry = $this->app->make(ModuleRegistryInterface::class);
        if (! $registry->has('dressnmore.customer.binding')) {
            $registry->add($this->app->make(CustomerBindingModule::class));
        }
    }
}
