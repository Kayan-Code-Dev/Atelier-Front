<?php

declare(strict_types=1);

namespace DressnMore\Aos\Context\Domain\Tenant;

/**
 * Branch slice for the Context Snapshot.
 */
final class BranchContext
{
    public function __construct(
        private readonly ?BranchId $branchId,
        private readonly ?string $name = null,
    ) {}

    public static function none(): self
    {
        return new self(null, null);
    }

    public function branchId(): ?BranchId
    {
        return $this->branchId;
    }

    public function name(): ?string
    {
        return $this->name;
    }
}
