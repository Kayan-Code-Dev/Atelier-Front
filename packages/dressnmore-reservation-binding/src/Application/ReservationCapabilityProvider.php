<?php

declare(strict_types=1);

namespace DressnMore\ReservationBinding\Application;

use DressnMore\ReservationBinding\Contracts\ReservationCapabilityProviderInterface;
use DressnMore\ReservationBinding\Domain\Tools\ReservationToolCatalog;

final class ReservationCapabilityProvider implements ReservationCapabilityProviderInterface
{
    public function capabilities(): array
    {
        $caps = [];
        foreach (ReservationToolCatalog::all() as $contract) {
            foreach ($contract->capabilities() as $cap) {
                $caps[] = $cap;
            }
        }

        return array_values(array_unique($caps));
    }

    public function supports(string $capability): bool
    {
        return in_array($capability, $this->capabilities(), true);
    }
}
