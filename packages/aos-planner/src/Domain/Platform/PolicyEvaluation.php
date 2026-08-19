<?php

declare(strict_types=1);

namespace DressnMore\Aos\Planner\Domain\Platform;

final class PolicyEvaluation
{
    /**
     * @param list<string> $requiredApprovals
     * @param list<string> $violations
     */
    public function __construct(
        private readonly bool $allowed,
        private readonly array $requiredApprovals = [],
        private readonly array $violations = [],
        private readonly string $notes = '',
    ) {}

    public function allowed(): bool { return $this->allowed; }
    /** @return list<string> */
    public function requiredApprovals(): array { return $this->requiredApprovals; }
    /** @return list<string> */
    public function violations(): array { return $this->violations; }
    public function notes(): string { return $this->notes; }
}
