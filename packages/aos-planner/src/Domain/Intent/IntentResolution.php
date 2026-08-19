<?php

declare(strict_types=1);

namespace DressnMore\Aos\Planner\Domain\Intent;

/**
 * Result of Intent Resolution for one planning cycle.
 */
final class IntentResolution
{
    /**
     * @param  list<ResolvedIntent>  $intents
     */
    public function __construct(
        private readonly IntentKind $kind,
        private readonly array $intents,
        private readonly float $overallConfidence,
        private readonly string $reason = '',
    ) {}

    public static function unknown(string $reason = 'no intents matched'): self
    {
        return new self(
            IntentKind::Unknown,
            [new ResolvedIntent(IntentCode::unknown(), 0.0, [])],
            0.0,
            $reason,
        );
    }

    /**
     * @param  list<ResolvedIntent>  $intents
     */
    public static function of(IntentKind $kind, array $intents, float $overallConfidence, string $reason = ''): self
    {
        return new self($kind, $intents, $overallConfidence, $reason);
    }

    public function kind(): IntentKind
    {
        return $this->kind;
    }

    /**
     * @return list<ResolvedIntent>
     */
    public function intents(): array
    {
        return $this->intents;
    }

    public function overallConfidence(): float
    {
        return $this->overallConfidence;
    }

    public function reason(): string
    {
        return $this->reason;
    }

    public function isActionable(): bool
    {
        return in_array($this->kind, [IntentKind::Single, IntentKind::Multi], true);
    }
}
