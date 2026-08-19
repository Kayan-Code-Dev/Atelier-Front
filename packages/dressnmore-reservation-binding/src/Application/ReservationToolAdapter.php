<?php

declare(strict_types=1);

namespace DressnMore\ReservationBinding\Application;

use DressnMore\ReservationBinding\Contracts\ReservationToolAdapterInterface;
use DressnMore\ReservationBinding\Domain\Tools\ReservationToolCatalog;
use DressnMore\ReservationBinding\Domain\Tools\ReservationToolName;

/**
 * Contract-only adapter: exposes Reservation tool contracts to AOS Tool Gateway.
 */
final class ReservationToolAdapter implements ReservationToolAdapterInterface
{
    public function contracts(): array
    {
        return ReservationToolCatalog::all();
    }

    public function supports(string $toolName): bool
    {
        foreach (ReservationToolName::cases() as $case) {
            if ($case->value === $toolName) {
                return true;
            }
        }

        return false;
    }
}
