<?php

declare(strict_types=1);

namespace DressnMore\ReservationBinding\Application;

use DressnMore\ReservationBinding\Contracts\ReservationEventPublisherInterface;
use DressnMore\ReservationBinding\Contracts\ReservationSnapshotBuilderInterface;
use DressnMore\ReservationBinding\Domain\Events\ReservationDomainEvent;
use DressnMore\ReservationBinding\Domain\Reservation\ReservationReadModel;
use DressnMore\ReservationBinding\Domain\Snapshot\ReservationSnapshot;

final class ReservationSnapshotBuilder implements ReservationSnapshotBuilderInterface
{
    public function __construct(private readonly ?ReservationEventPublisherInterface $events = null) {}

    public function build(ReservationReadModel $reservation): ReservationSnapshot
    {
        $summary = sprintf(
            '%s — %s on %s %s (%s)',
            $reservation->customerDisplayName(),
            $reservation->serviceName(),
            $reservation->date(),
            $reservation->time(),
            $reservation->status(),
        );

        $snapshot = new ReservationSnapshot(
            $reservation->id(),
            $reservation->tenantId(),
            $reservation->customerDisplayName(),
            $reservation->serviceName(),
            $reservation->date(),
            $reservation->time(),
            $reservation->status(),
            $reservation->assignedEmployeeName(),
            count($reservation->reminders()),
            $summary,
            [
                'customerRef' => $reservation->customerRef(),
                'serviceRef' => $reservation->serviceRef(),
            ],
        );

        $this->events?->publish(ReservationDomainEvent::reservationSnapshotBuilt([
            'reservationId' => $reservation->id()->toString(),
            'tenantId' => $reservation->tenantId(),
        ]));

        return $snapshot;
    }
}
