<?php

declare(strict_types=1);

namespace DressnMore\ReservationBinding;

use DressnMore\Aos\Core\Module\Contracts\ModuleRegistryInterface;
use DressnMore\ReservationBinding\Application\ReservationAvailabilityResolver;
use DressnMore\ReservationBinding\Application\ReservationCapabilityProvider;
use DressnMore\ReservationBinding\Application\ReservationContextBuilder;
use DressnMore\ReservationBinding\Application\ReservationIntentMapper;
use DressnMore\ReservationBinding\Application\ReservationPolicyAdapter;
use DressnMore\ReservationBinding\Application\ReservationReminderBuilder;
use DressnMore\ReservationBinding\Application\ReservationSnapshotBuilder;
use DressnMore\ReservationBinding\Application\ReservationTimelineBuilder;
use DressnMore\ReservationBinding\Application\ReservationToolAdapter;
use DressnMore\ReservationBinding\Contracts\ReservationAvailabilityPortInterface;
use DressnMore\ReservationBinding\Contracts\ReservationAvailabilityResolverInterface;
use DressnMore\ReservationBinding\Contracts\ReservationCapabilityProviderInterface;
use DressnMore\ReservationBinding\Contracts\ReservationContextBuilderInterface;
use DressnMore\ReservationBinding\Contracts\ReservationEventPublisherInterface;
use DressnMore\ReservationBinding\Contracts\ReservationIntentMapperInterface;
use DressnMore\ReservationBinding\Contracts\ReservationPolicyAdapterInterface;
use DressnMore\ReservationBinding\Contracts\ReservationReadModelPortInterface;
use DressnMore\ReservationBinding\Contracts\ReservationReminderBuilderInterface;
use DressnMore\ReservationBinding\Contracts\ReservationSnapshotBuilderInterface;
use DressnMore\ReservationBinding\Contracts\ReservationTimelineBuilderInterface;
use DressnMore\ReservationBinding\Contracts\ReservationToolAdapterInterface;
use DressnMore\ReservationBinding\Infrastructure\InMemory\InMemoryReservationAvailabilityPort;
use DressnMore\ReservationBinding\Infrastructure\InMemory\InMemoryReservationEventPublisher;
use DressnMore\ReservationBinding\Infrastructure\InMemory\InMemoryReservationReadModelPort;
use DressnMore\ReservationBinding\Module\ReservationBindingModule;
use Illuminate\Support\ServiceProvider;

final class ReservationBindingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(InMemoryReservationReadModelPort::class);
        $this->app->singleton(ReservationReadModelPortInterface::class, InMemoryReservationReadModelPort::class);
        $this->app->singleton(InMemoryReservationAvailabilityPort::class);
        $this->app->singleton(ReservationAvailabilityPortInterface::class, InMemoryReservationAvailabilityPort::class);
        $this->app->singleton(ReservationEventPublisherInterface::class, InMemoryReservationEventPublisher::class);
        $this->app->singleton(ReservationToolAdapter::class);
        $this->app->singleton(ReservationToolAdapterInterface::class, ReservationToolAdapter::class);
        $this->app->singleton(ReservationCapabilityProvider::class);
        $this->app->singleton(ReservationCapabilityProviderInterface::class, ReservationCapabilityProvider::class);
        $this->app->singleton(ReservationIntentMapper::class);
        $this->app->singleton(ReservationIntentMapperInterface::class, ReservationIntentMapper::class);
        $this->app->singleton(ReservationPolicyAdapter::class);
        $this->app->singleton(ReservationPolicyAdapterInterface::class, ReservationPolicyAdapter::class);
        $this->app->singleton(ReservationAvailabilityResolver::class);
        $this->app->singleton(ReservationAvailabilityResolverInterface::class, ReservationAvailabilityResolver::class);
        $this->app->singleton(ReservationContextBuilder::class);
        $this->app->singleton(ReservationContextBuilderInterface::class, ReservationContextBuilder::class);
        $this->app->singleton(ReservationSnapshotBuilder::class);
        $this->app->singleton(ReservationSnapshotBuilderInterface::class, ReservationSnapshotBuilder::class);
        $this->app->singleton(ReservationTimelineBuilder::class);
        $this->app->singleton(ReservationTimelineBuilderInterface::class, ReservationTimelineBuilder::class);
        $this->app->singleton(ReservationReminderBuilder::class);
        $this->app->singleton(ReservationReminderBuilderInterface::class, ReservationReminderBuilder::class);
        $this->app->singleton(ReservationBindingModule::class);
    }

    public function boot(): void
    {
        /** @var ModuleRegistryInterface $registry */
        $registry = $this->app->make(ModuleRegistryInterface::class);
        if (! $registry->has('dressnmore.reservation.binding')) {
            $registry->add($this->app->make(ReservationBindingModule::class));
        }
    }
}
