<?php

declare(strict_types=1);

namespace DressnMore\ReservationBinding\Infrastructure\InMemory;

use DressnMore\ReservationBinding\Contracts\ReservationEventPublisherInterface;
use DressnMore\ReservationBinding\Domain\Events\ReservationDomainEvent;

/**
 * Test/demo event sink — bridge to aos-events in a later sprint.
 */
final class InMemoryReservationEventPublisher implements ReservationEventPublisherInterface
{
    /** @var list<ReservationDomainEvent> */
    private array $events = [];

    public function publish(ReservationDomainEvent $event): void
    {
        $this->events[] = $event;
    }

    /**
     * @return list<ReservationDomainEvent>
     */
    public function all(): array
    {
        return $this->events;
    }
}
