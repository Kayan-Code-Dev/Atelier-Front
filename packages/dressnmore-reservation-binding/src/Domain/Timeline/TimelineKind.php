<?php

declare(strict_types=1);

namespace DressnMore\ReservationBinding\Domain\Timeline;

enum TimelineKind: string
{
    case Creation = 'creation';
    case Updates = 'updates';
    case Reschedule = 'reschedule';
    case Cancellation = 'cancellation';
    case Reminder = 'reminder';
    case Arrival = 'arrival';
    case Completion = 'completion';
}
