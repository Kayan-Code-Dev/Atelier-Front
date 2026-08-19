<?php

declare(strict_types=1);

namespace DressnMore\Aos\Planner\Domain\Intent;

/**
 * One resolved intent candidate with confidence.
 */
final class ResolvedIntent
{
    /**
     * @param  list<string>  $signals  matched heuristic signals
     */
    public function __construct(
        private readonly IntentCode $code,
        private readonly float $confidence,
        private readonly array $signals = [],
    ) {
        if ($confidence < 0.0 || $confidence > 1.0) {
            throw new \InvalidArgumentException('Intent confidence must be between 0 and 1.');
        }
    }

    public function code(): IntentCode
    {
        return $this->code;
    }

    public function confidence(): float
    {
        return $this->confidence;
    }

    /**
     * @return list<string>
     */
    public function signals(): array
    {
        return $this->signals;
    }
}
