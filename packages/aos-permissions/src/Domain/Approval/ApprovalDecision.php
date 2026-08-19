<?php

declare(strict_types=1);

namespace DressnMore\Aos\Permissions\Domain\Approval;

use DateTimeImmutable;

final class ApprovalDecision
{
    public function __construct(
        private readonly bool $granted,
        private readonly string $decidedBy,
        private readonly string $reason,
        private readonly DateTimeImmutable $decidedAt = new DateTimeImmutable(),
    ) {}

    public static function grant(string $decidedBy, string $reason = ''): self
    {
        return new self(true, $decidedBy, $reason);
    }

    public static function reject(string $decidedBy, string $reason = ''): self
    {
        return new self(false, $decidedBy, $reason);
    }

    public function isGranted(): bool
    {
        return $this->granted;
    }

    public function decidedBy(): string
    {
        return $this->decidedBy;
    }

    public function reason(): string
    {
        return $this->reason;
    }

    public function decidedAt(): DateTimeImmutable
    {
        return $this->decidedAt;
    }
}
