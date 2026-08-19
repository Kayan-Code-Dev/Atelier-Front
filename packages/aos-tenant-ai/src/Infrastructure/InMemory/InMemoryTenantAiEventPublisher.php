<?php

declare(strict_types=1);

namespace DressnMore\Aos\TenantAi\Infrastructure\InMemory;

use DressnMore\Aos\TenantAi\Contracts\TenantAiEventPublisherInterface;
use DressnMore\Aos\TenantAi\Domain\Events\TenantAiDomainEvent;

final class InMemoryTenantAiEventPublisher implements TenantAiEventPublisherInterface
{
    /** @var list<TenantAiDomainEvent> */
    private array $events = [];

    public function publish(TenantAiDomainEvent $event): void
    {
        $this->events[] = $event;
    }

    /**
     * @return list<TenantAiDomainEvent>
     */
    public function all(): array
    {
        return $this->events;
    }
}
