<?php

declare(strict_types=1);

namespace DressnMore\ReservationBinding\Contracts;

use DressnMore\ReservationBinding\Domain\Availability\AvailabilityResult;
use DressnMore\ReservationBinding\Domain\Availability\AvailabilitySlot;

interface ReservationAvailabilityPortInterface
{
    public function check(
        string $tenantId,
        string $serviceRef,
        string $date,
        ?string $time = null,
        ?string $employeeRef = null,
    ): AvailabilityResult;

    /**
     * @return list<AvailabilitySlot>
     */
    public function slots(
        string $tenantId,
        string $serviceRef,
        string $dateFrom,
        ?string $dateTo = null,
        ?string $employeeRef = null,
    ): array;
}
