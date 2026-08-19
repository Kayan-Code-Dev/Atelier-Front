<?php

declare(strict_types=1);

namespace DressnMore\Aos\Memory\Domain\Memory;

use InvalidArgumentException;

final class ImportanceScore
{
    public function __construct(
        private readonly float $value,
    ) {
        if ($value < 0.0 || $value > 1.0) {
            throw new InvalidArgumentException('ImportanceScore must be between 0 and 1.');
        }
    }

    public static function of(float $value): self
    {
        return new self(max(0.0, min(1.0, $value)));
    }

    public function value(): float
    {
        return $this->value;
    }
}
