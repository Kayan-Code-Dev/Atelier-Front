<?php

declare(strict_types=1);

namespace DressnMore\ReservationBinding\Domain\Availability;

final class AvailabilitySlot
{
    public function __construct(
        private readonly string $date,
        private readonly string $time,
        private readonly string $serviceRef,
        private readonly ?string $employeeRef = null,
        private readonly bool $available = true,
    ) {}

    public function date(): string { return $this->date; }
    public function time(): string { return $this->time; }
    public function serviceRef(): string { return $this->serviceRef; }
    public function employeeRef(): ?string { return $this->employeeRef; }
    public function available(): bool { return $this->available; }
}
