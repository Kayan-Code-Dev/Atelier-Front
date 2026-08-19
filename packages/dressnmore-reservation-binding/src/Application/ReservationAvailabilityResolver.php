<?php

declare(strict_types=1);

namespace DressnMore\ReservationBinding\Application;

use DressnMore\ReservationBinding\Contracts\ReservationAvailabilityPortInterface;
use DressnMore\ReservationBinding\Contracts\ReservationAvailabilityResolverInterface;
use DressnMore\ReservationBinding\Domain\Availability\AvailabilityResult;

/**
 * Binding composer over availability port — not DressnMore scheduling logic.
 */
final class ReservationAvailabilityResolver implements ReservationAvailabilityResolverInterface
{
    public function __construct(private readonly ReservationAvailabilityPortInterface $port) {}

    public function resolve(
        string $tenantId,
        string $serviceRef,
        string $date,
        ?string $time = null,
        ?string $employeeRef = null,
    ): AvailabilityResult {
        return $this->port->check($tenantId, $serviceRef, $date, $time, $employeeRef);
    }

    public function availableSlots(
        string $tenantId,
        string $serviceRef,
        string $dateFrom,
        ?string $dateTo = null,
        ?string $employeeRef = null,
    ): array {
        return $this->port->slots($tenantId, $serviceRef, $dateFrom, $dateTo, $employeeRef);
    }
}
