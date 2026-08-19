<?php

declare(strict_types=1);

namespace DressnMore\Aos\Context\Domain\Localization;

/**
 * Immutable business-hours window description (opaque schedule text + open flag).
 */
final class BusinessHours
{
    /**
     * @param  list<string>  $scheduleLines  e.g. "Sun-Thu 10:00-22:00"
     */
    public function __construct(
        private readonly array $scheduleLines,
        private readonly bool $isOpenNow,
        private readonly ?string $nextOpenHint = null,
    ) {}

    public static function unknown(): self
    {
        return new self([], false, null);
    }

    /**
     * @return list<string>
     */
    public function scheduleLines(): array
    {
        return $this->scheduleLines;
    }

    public function isOpenNow(): bool
    {
        return $this->isOpenNow;
    }

    public function nextOpenHint(): ?string
    {
        return $this->nextOpenHint;
    }
}
