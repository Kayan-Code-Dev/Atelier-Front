<?php

declare(strict_types=1);

namespace DressnMore\ReservationBinding\Contracts;

use DressnMore\ReservationBinding\Domain\Events\ReservationDomainEvent;

interface ReservationEventPublisherInterface
{
    public function publish(ReservationDomainEvent $event): void;
}
