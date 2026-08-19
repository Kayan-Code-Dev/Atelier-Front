<?php

declare(strict_types=1);

namespace DressnMore\ReservationBinding\Contracts;

interface ReservationPolicyAdapterInterface
{
    public function requiresApproval(string $toolName): bool;

    public function riskLevel(string $toolName): string;
}
