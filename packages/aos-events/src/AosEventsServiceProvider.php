<?php

declare(strict_types=1);

namespace DressnMore\Aos\Events;

use DressnMore\Aos\Core\Module\Contracts\ModuleRegistryInterface;
use DressnMore\Aos\Events\Bus\EventBus;
use DressnMore\Aos\Events\Bus\IlluminateEventDispatcher;
use DressnMore\Aos\Events\Contracts\DispatcherInterface;
use DressnMore\Aos\Events\Contracts\EventBusInterface;
use DressnMore\Aos\Events\Contracts\PublisherInterface;
use DressnMore\Aos\Events\Module\EventsModule;
use Illuminate\Support\ServiceProvider;

/**
 * Registers AOS event bus contracts and the events foundation module.
 */
final class AosEventsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(DispatcherInterface::class, IlluminateEventDispatcher::class);
        $this->app->singleton(EventBus::class);
        $this->app->singleton(EventBusInterface::class, EventBus::class);
        $this->app->singleton(PublisherInterface::class, EventBus::class);
        $this->app->singleton(EventsModule::class);

        $this->app->afterResolving(ModuleRegistryInterface::class, function (ModuleRegistryInterface $registry): void {
            if (! $registry->has('aos.events')) {
                $registry->add($this->app->make(EventsModule::class));
            }
        });

        // Ensure registry receives the module even if already resolved.
        if ($this->app->resolved(ModuleRegistryInterface::class)) {
            /** @var ModuleRegistryInterface $registry */
            $registry = $this->app->make(ModuleRegistryInterface::class);
            if (! $registry->has('aos.events')) {
                $registry->add($this->app->make(EventsModule::class));
            }
        }
    }

    public function boot(): void
    {
        /** @var ModuleRegistryInterface $registry */
        $registry = $this->app->make(ModuleRegistryInterface::class);
        if (! $registry->has('aos.events')) {
            $registry->add($this->app->make(EventsModule::class));
        }
    }
}
