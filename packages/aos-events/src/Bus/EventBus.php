<?php

declare(strict_types=1);

namespace DressnMore\Aos\Events\Bus;

use DressnMore\Aos\Events\Contracts\EventBusInterface;
use DressnMore\Aos\Events\Contracts\PublisherInterface;
use DressnMore\Aos\Events\Contracts\SubscriberInterface;
use Illuminate\Contracts\Events\Dispatcher;

/**
 * Foundation event bus: publish + subscribe via Laravel dispatcher.
 */
final class EventBus implements EventBusInterface, PublisherInterface
{
    public function __construct(
        private readonly Dispatcher $dispatcher,
    ) {}

    public function publish(object $event): void
    {
        $this->dispatcher->dispatch($event);
    }

    public function subscribe(string $event, string|callable $listener): void
    {
        $this->dispatcher->listen($event, $listener);
    }

    public function registerSubscriber(SubscriberInterface $subscriber): void
    {
        foreach ($subscriber->subscriptions() as $event => $listeners) {
            foreach ($listeners as $listener) {
                $this->subscribe($event, $listener);
            }
        }
    }
}
