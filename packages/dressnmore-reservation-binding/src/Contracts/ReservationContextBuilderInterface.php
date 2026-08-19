<?php

declare(strict_types=1);

namespace DressnMore\ReservationBinding\Contracts;

use DressnMore\ReservationBinding\Domain\Context\ReservationContext;
use DressnMore\ReservationBinding\Domain\Reservation\ReservationReadModel;

interface ReservationContextBuilderInterface
{
    public function build(ReservationReadModel $reservation): ReservationContext;
}
