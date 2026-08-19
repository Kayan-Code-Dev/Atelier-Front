<?php

declare(strict_types=1);

namespace DressnMore\ReservationBinding\Contracts;

interface ReservationIntentMapperInterface
{
    public function map(string $intent): ?string;
}
