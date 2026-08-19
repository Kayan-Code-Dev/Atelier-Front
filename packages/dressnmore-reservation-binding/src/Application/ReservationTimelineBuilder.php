<?php

declare(strict_types=1);

namespace DressnMore\ReservationBinding\Application;

use DressnMore\ReservationBinding\Contracts\ReservationTimelineBuilderInterface;
use DressnMore\ReservationBinding\Domain\Reservation\ReservationReadModel;
use DressnMore\ReservationBinding\Domain\Timeline\ReservationTimeline;
use DressnMore\ReservationBinding\Domain\Timeline\TimelineEntry;
use DressnMore\ReservationBinding\Domain\Timeline\TimelineKind;

final class ReservationTimelineBuilder implements ReservationTimelineBuilderInterface
{
    public function build(ReservationReadModel $reservation): ReservationTimeline
    {
        $entries = [];

        $entries[] = new TimelineEntry(
            TimelineKind::Creation,
            $reservation->date().'T'.$reservation->time(),
            'Reservation created',
            $reservation->serviceName(),
        );

        foreach ($reservation->history() as $item) {
            $kind = match (strtolower((string) ($item['type'] ?? 'updates'))) {
                'reschedule', 'rescheduled' => TimelineKind::Reschedule,
                'cancel', 'cancelled', 'cancellation' => TimelineKind::Cancellation,
                'arrival', 'arrived' => TimelineKind::Arrival,
                'complete', 'completed', 'completion' => TimelineKind::Completion,
                'reminder' => TimelineKind::Reminder,
                default => TimelineKind::Updates,
            };
            $entries[] = new TimelineEntry(
                $kind,
                (string) $item['at'],
                ucfirst((string) ($item['type'] ?? 'update')),
                $item['detail'] ?? null,
            );
        }

        foreach ($reservation->reminders() as $reminder) {
            $entries[] = new TimelineEntry(
                TimelineKind::Reminder,
                (string) $reminder['at'],
                'Reminder',
                $reminder['channel'] ?? null,
                ['status' => $reminder['status'] ?? null],
            );
        }

        foreach ($reservation->timelineSeed() as $seed) {
            $kindValue = (string) ($seed['kind'] ?? TimelineKind::Updates->value);
            $kind = TimelineKind::tryFrom($kindValue) ?? TimelineKind::Updates;
            $entries[] = new TimelineEntry(
                $kind,
                (string) ($seed['at'] ?? $reservation->date()),
                (string) ($seed['title'] ?? $kind->value),
                isset($seed['detail']) ? (string) $seed['detail'] : null,
            );
        }

        return new ReservationTimeline(
            $reservation->id()->toString(),
            $reservation->tenantId(),
            $entries,
        );
    }
}
