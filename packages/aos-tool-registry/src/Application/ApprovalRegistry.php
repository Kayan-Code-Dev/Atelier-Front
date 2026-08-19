<?php

declare(strict_types=1);

namespace DressnMore\Aos\ToolRegistry\Application;

use DressnMore\Aos\ToolRegistry\Contracts\ApprovalRegistryInterface;
use DressnMore\Aos\ToolRegistry\Domain\Approval\ApprovalDescriptor;

final class ApprovalRegistry implements ApprovalRegistryInterface
{
    /** @var array<string, ApprovalDescriptor> */
    private array $approvals = [];

    public function register(ApprovalDescriptor $descriptor): void
    {
        $this->approvals[$descriptor->name()] = $descriptor;
    }

    public function get(string $name): ?ApprovalDescriptor
    {
        return $this->approvals[$name] ?? null;
    }

    public function all(): array
    {
        return array_values($this->approvals);
    }
}
