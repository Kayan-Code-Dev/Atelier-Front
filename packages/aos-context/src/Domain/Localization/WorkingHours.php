<?php

declare(strict_types=1);

namespace DressnMore\Aos\Context\Domain\Localization;

/**
 * Working-hours view for staff / AI availability (distinct from customer-facing business hours).
 */
final class WorkingHours
{
    public function __construct(
        private readonly bool $staffAvailable,
        private readonly BusinessHours $hours,
    ) {}

    public static function unknown(): self
    {
        return new self(false, BusinessHours::unknown());
    }

    public function staffAvailable(): bool
    {
        return $this->staffAvailable;
    }

    public function hours(): BusinessHours
    {
        return $this->hours;
    }
}
