<?php

declare(strict_types=1);

namespace DressnMore\ReservationBinding\Contracts;

interface ReservationCapabilityProviderInterface
{
    /**
     * @return list<string>
     */
    public function capabilities(): array;

    public function supports(string $capability): bool;
}
