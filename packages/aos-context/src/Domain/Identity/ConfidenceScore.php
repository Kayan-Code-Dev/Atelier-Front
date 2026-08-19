<?php

declare(strict_types=1);

namespace DressnMore\Aos\Context\Domain\Identity;

/**
 * Confidence score in [0.0, 1.0] for identity matching.
 */
final class ConfidenceScore
{
    public function __construct(
        private readonly float $value,
    ) {
        if ($value < 0.0 || $value > 1.0) {
            throw new \InvalidArgumentException('ConfidenceScore must be between 0 and 1.');
        }
    }

    public static function of(float $value): self
    {
        return new self($value);
    }

    public static function none(): self
    {
        return new self(0.0);
    }

    public static function exact(): self
    {
        return new self(1.0);
    }

    public function value(): float
    {
        return $this->value;
    }

    public function isAtLeast(float $threshold): bool
    {
        return $this->value >= $threshold;
    }

    public function isHigherThan(self $other): bool
    {
        return $this->value > $other->value;
    }
}
