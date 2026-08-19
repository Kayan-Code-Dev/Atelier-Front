<?php

declare(strict_types=1);

namespace DressnMore\Aos\ToolRegistry\Contracts;

use DressnMore\Aos\ToolRegistry\Domain\Approval\ApprovalDescriptor;

interface ApprovalRegistryInterface
{
    public function register(ApprovalDescriptor $descriptor): void;

    public function get(string $name): ?ApprovalDescriptor;

    /**
     * @return list<ApprovalDescriptor>
     */
    public function all(): array;
}
