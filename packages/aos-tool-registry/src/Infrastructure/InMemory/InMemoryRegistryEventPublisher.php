<?php

declare(strict_types=1);

namespace DressnMore\Aos\ToolRegistry\Infrastructure\InMemory;

use DressnMore\Aos\ToolRegistry\Contracts\RegistryEventPublisherInterface;
use DressnMore\Aos\ToolRegistry\Domain\Events\RegistryDomainEvent;

final class InMemoryRegistryEventPublisher implements RegistryEventPublisherInterface
{
    /** @var list<RegistryDomainEvent> */
    private array $events = [];

    public function publish(RegistryDomainEvent $event): void
    {
        $this->events[] = $event;
    }

    /**
     * @return list<RegistryDomainEvent>
     */
    public function all(): array
    {
        return $this->events;
    }
}
