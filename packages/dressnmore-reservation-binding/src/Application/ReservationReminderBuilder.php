<?php

declare(strict_types=1);

namespace DressnMore\ReservationBinding\Application;

use DressnMore\ReservationBinding\Contracts\ReservationEventPublisherInterface;
use DressnMore\ReservationBinding\Contracts\ReservationReminderBuilderInterface;
use DressnMore\ReservationBinding\Domain\Events\ReservationDomainEvent;
use DressnMore\ReservationBinding\Domain\Reminder\ReminderPlan;
use DressnMore\ReservationBinding\Domain\Reservation\ReservationReadModel;

/**
 * Builds conceptual reminder plans from read-model seeds — no channel delivery.
 */
final class ReservationReminderBuilder implements ReservationReminderBuilderInterface
{
    public function __construct(private readonly ?ReservationEventPublisherInterface $events = null) {}

    public function build(ReservationReadModel $reservation): ReminderPlan
    {
        $items = [];
        foreach ($reservation->reminders() as $reminder) {
            $items[] = [
                'channel' => (string) ($reminder['channel'] ?? 'whatsapp'),
                'at' => (string) $reminder['at'],
                'message' => 'Reservation reminder for '.$reservation->serviceName(),
            ];
        }

        if ($items === []) {
            $items[] = [
                'channel' => 'whatsapp',
                'at' => $reservation->date().'T'.'09:00',
                'message' => 'Default day-of reminder placeholder',
            ];
        }

        $plan = new ReminderPlan(
            $reservation->id()->toString(),
            $reservation->tenantId(),
            $items,
        );

        $this->events?->publish(ReservationDomainEvent::reservationReminderScheduled([
            'reservationId' => $reservation->id()->toString(),
            'tenantId' => $reservation->tenantId(),
            'count' => $plan->count(),
        ]));

        return $plan;
    }
}
