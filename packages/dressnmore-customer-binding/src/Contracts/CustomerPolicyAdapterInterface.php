<?php

declare(strict_types=1);

namespace DressnMore\CustomerBinding\Contracts;

interface CustomerPolicyAdapterInterface
{
    public function requiresApproval(string $toolName): bool;

    public function riskLevel(string $toolName): string;
}
