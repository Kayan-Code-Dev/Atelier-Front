<?php

declare(strict_types=1);

namespace DressnMore\Aos\Permissions\Domain\Risk;

final class RiskEvaluation
{
    public function __construct(
        private readonly RiskLevel $level,
        private readonly string $reason,
        private readonly bool $requiresApproval,
        private readonly bool $requiresHuman,
    ) {}

    public function level(): RiskLevel
    {
        return $this->level;
    }

    public function reason(): string
    {
        return $this->reason;
    }

    public function requiresApproval(): bool
    {
        return $this->requiresApproval;
    }

    public function requiresHuman(): bool
    {
        return $this->requiresHuman;
    }
}
