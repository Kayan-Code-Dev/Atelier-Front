<?php

declare(strict_types=1);

namespace DressnMore\ReservationBinding\Contracts;

use DressnMore\ReservationBinding\Domain\Reservation\ReservationReadModel;
use DressnMore\ReservationBinding\Domain\Timeline\ReservationTimeline;

interface ReservationTimelineBuilderInterface
{
    public function build(ReservationReadModel $reservation): ReservationTimeline;
}
