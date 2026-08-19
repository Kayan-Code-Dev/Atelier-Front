<?php

declare(strict_types=1);

namespace DressnMore\Aos\Planner\Domain\Platform;

final class AnalyzedIntent
{
    /**
     * @param list<string> $signals
     * @param list<string> $toolPlan
     * @param list<string> $requiredCapabilities
     */
    public function __construct(
        private readonly string $intent,
        private readonly float $confidence,
        private readonly array $signals = [],
        private readonly array $toolPlan = [],
        private readonly array $requiredCapabilities = [],
        private readonly ?string $policy = null,
        private readonly ?string $approval = null,
        private readonly bool $known = true,
    ) {}

    public function intent(): string { return $this->intent; }
    public function confidence(): float { return $this->confidence; }
    /** @return list<string> */
    public function signals(): array { return $this->signals; }
    /** @return list<string> */
    public function toolPlan(): array { return $this->toolPlan; }
    /** @return list<string> */
    public function requiredCapabilities(): array { return $this->requiredCapabilities; }
    public function policy(): ?string { return $this->policy; }
    public function approval(): ?string { return $this->approval; }
    public function known(): bool { return $this->known; }

    public static function unknown(string $reason = 'unknown'): self
    {
        return new self('Unknown', 0.0, [$reason], [], [], null, null, false);
    }
}
