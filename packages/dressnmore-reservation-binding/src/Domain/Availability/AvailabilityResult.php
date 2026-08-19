<?php

declare(strict_types=1);

namespace DressnMore\ReservationBinding\Domain\Availability;

final class AvailabilityResult
{
    /**
     * @param list<AvailabilitySlot> $slots
     * @param list<string> $conflicts
     */
    public function __construct(
        private readonly bool $available,
        private readonly array $slots = [],
        private readonly array $conflicts = [],
    ) {}

    public function available(): bool { return $this->available; }
    /** @return list<AvailabilitySlot> */
    public function slots(): array { return $this->slots; }
    /** @return list<string> */
    public function conflicts(): array { return $this->conflicts; }
}
