<?php

declare(strict_types=1);

namespace DressnMore\ReservationBinding\Contracts;

use DressnMore\ReservationBinding\Domain\Reservation\ReservationId;
use DressnMore\ReservationBinding\Domain\Reservation\ReservationReadModel;

interface ReservationReadModelPortInterface
{
    public function findById(string $tenantId, ReservationId $reservationId): ?ReservationReadModel;

    /**
     * @return list<ReservationReadModel>
     */
    public function findByCustomer(string $tenantId, string $customerRef): array;

    /**
     * @return list<ReservationReadModel>
     */
    public function findByDate(string $tenantId, string $date): array;
}
