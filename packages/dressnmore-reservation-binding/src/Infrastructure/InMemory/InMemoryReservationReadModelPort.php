<?php

declare(strict_types=1);

namespace DressnMore\ReservationBinding\Infrastructure\InMemory;

use DressnMore\ReservationBinding\Contracts\ReservationReadModelPortInterface;
use DressnMore\ReservationBinding\Domain\Reservation\ReservationId;
use DressnMore\ReservationBinding\Domain\Reservation\ReservationReadModel;

/**
 * Test/demo read-model port only — not a DressnMore repository.
 */
final class InMemoryReservationReadModelPort implements ReservationReadModelPortInterface
{
    /** @var array<string, ReservationReadModel> */
    private array $byId = [];

    public function seed(ReservationReadModel $reservation): void
    {
        $this->byId[$reservation->tenantId().':'.$reservation->id()->toString()] = $reservation;
    }

    public function findById(string $tenantId, ReservationId $reservationId): ?ReservationReadModel
    {
        return $this->byId[$tenantId.':'.$reservationId->toString()] ?? null;
    }

    public function findByCustomer(string $tenantId, string $customerRef): array
    {
        $out = [];
        foreach ($this->byId as $key => $reservation) {
            if (! str_starts_with($key, $tenantId.':')) {
                continue;
            }
            if ($reservation->customerRef() === $customerRef) {
                $out[] = $reservation;
            }
        }

        return $out;
    }

    public function findByDate(string $tenantId, string $date): array
    {
        $out = [];
        foreach ($this->byId as $key => $reservation) {
            if (! str_starts_with($key, $tenantId.':')) {
                continue;
            }
            if ($reservation->date() === $date) {
                $out[] = $reservation;
            }
        }

        return $out;
    }
}
