<?php

declare(strict_types=1);

namespace DressnMore\ReservationBinding\Domain\Reservation;

final class ReservationId
{
    private function __construct(private readonly string $value) {}

    public static function fromString(string $value): self
    {
        $trimmed = trim($value);
        if ($trimmed === '') {
            throw new \InvalidArgumentException('ReservationId cannot be empty.');
        }

        return new self($trimmed);
    }

    public function toString(): string
    {
        return $this->value;
    }
}
