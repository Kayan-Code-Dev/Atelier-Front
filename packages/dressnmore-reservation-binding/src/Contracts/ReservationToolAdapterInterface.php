<?php

declare(strict_types=1);

namespace DressnMore\ReservationBinding\Contracts;

use DressnMore\ReservationBinding\Domain\Tools\ReservationToolContract;

interface ReservationToolAdapterInterface
{
    /**
     * @return list<ReservationToolContract>
     */
    public function contracts(): array;

    public function supports(string $toolName): bool;
}
