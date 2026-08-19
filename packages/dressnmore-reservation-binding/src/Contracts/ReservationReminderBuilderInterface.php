<?php

declare(strict_types=1);

namespace DressnMore\ReservationBinding\Contracts;

use DressnMore\ReservationBinding\Domain\Reminder\ReminderPlan;
use DressnMore\ReservationBinding\Domain\Reservation\ReservationReadModel;

interface ReservationReminderBuilderInterface
{
    public function build(ReservationReadModel $reservation): ReminderPlan;
}
