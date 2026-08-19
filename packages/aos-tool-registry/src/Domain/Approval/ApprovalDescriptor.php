<?php

declare(strict_types=1);

namespace DressnMore\Aos\ToolRegistry\Domain\Approval;

use DressnMore\Aos\ToolRegistry\Domain\Tool\ApprovalRequirement;

final class ApprovalDescriptor
{
    public function __construct(
        private readonly string $name,
        private readonly ApprovalRequirement $requirement,
        private readonly string $description = '',
    ) {}

    public function name(): string { return $this->name; }
    public function requirement(): ApprovalRequirement { return $this->requirement; }
    public function description(): string { return $this->description; }
}
