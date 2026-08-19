<?php

declare(strict_types=1);

namespace DressnMore\Aos\Planner\Domain\Platform;

final class CapabilityMatch
{
    /**
     * @param list<string> $required
     * @param list<string> $matched
     * @param list<string> $missing
     */
    public function __construct(
        private readonly array $required,
        private readonly array $matched,
        private readonly array $missing = [],
    ) {}

    /** @return list<string> */
    public function required(): array { return $this->required; }
    /** @return list<string> */
    public function matched(): array { return $this->matched; }
    /** @return list<string> */
    public function missing(): array { return $this->missing; }
    public function ok(): bool { return $this->missing === []; }
}
