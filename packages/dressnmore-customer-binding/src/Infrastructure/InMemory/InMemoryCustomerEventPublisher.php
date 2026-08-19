<?php

declare(strict_types=1);

namespace DressnMore\CustomerBinding\Infrastructure\InMemory;

use DressnMore\CustomerBinding\Contracts\CustomerEventPublisherInterface;

final class InMemoryCustomerEventPublisher implements CustomerEventPublisherInterface
{
    /** @var list<object> */
    private array $events = [];

    public function publish(object $event): void
    {
        $this->events[] = $event;
    }

    /**
     * @return list<object>
     */
    public function all(): array
    {
        return $this->events;
    }
}
