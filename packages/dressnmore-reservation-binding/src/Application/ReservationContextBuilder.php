<?php

declare(strict_types=1);

namespace DressnMore\ReservationBinding\Application;

use DressnMore\ReservationBinding\Contracts\ReservationContextBuilderInterface;
use DressnMore\ReservationBinding\Contracts\ReservationEventPublisherInterface;
use DressnMore\ReservationBinding\Domain\Context\ReservationContext;
use DressnMore\ReservationBinding\Domain\Events\ReservationDomainEvent;
use DressnMore\ReservationBinding\Domain\Reservation\ReservationReadModel;

final class ReservationContextBuilder implements ReservationContextBuilderInterface
{
    public function __construct(private readonly ?ReservationEventPublisherInterface $events = null) {}

    public function build(ReservationReadModel $reservation): ReservationContext
    {
        $context = new ReservationContext(
            $reservation->id(),
            $reservation->tenantId(),
            [
                'id' => $reservation->id()->toString(),
                'status' => $reservation->status(),
            ],
            [
                'ref' => $reservation->customerRef(),
                'name' => $reservation->customerDisplayName(),
            ],
            [
                'ref' => $reservation->serviceRef(),
                'name' => $reservation->serviceName(),
            ],
            $reservation->date(),
            $reservation->time(),
            [
                'ref' => $reservation->assignedEmployeeRef(),
                'name' => $reservation->assignedEmployeeName(),
            ],
            $reservation->status(),
            $reservation->notes(),
            $reservation->history(),
            $reservation->reminders(),
        );

        $this->events?->publish(ReservationDomainEvent::reservationContextBuilt([
            'reservationId' => $reservation->id()->toString(),
            'tenantId' => $reservation->tenantId(),
        ]));

        return $context;
    }
}
