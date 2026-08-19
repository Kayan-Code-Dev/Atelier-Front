<?php

declare(strict_types=1);

namespace DressnMore\ReservationBinding\Application;

use DressnMore\ReservationBinding\Contracts\ReservationPolicyAdapterInterface;
use DressnMore\ReservationBinding\Domain\Tools\ApprovalPolicy;
use DressnMore\ReservationBinding\Domain\Tools\ReservationToolCatalog;
use DressnMore\ReservationBinding\Domain\Tools\ReservationToolName;

final class ReservationPolicyAdapter implements ReservationPolicyAdapterInterface
{
    public function requiresApproval(string $toolName): bool
    {
        $contract = ReservationToolCatalog::get(ReservationToolName::from($toolName));

        return in_array($contract->approvalPolicy(), [ApprovalPolicy::Often, ApprovalPolicy::Always], true);
    }

    public function riskLevel(string $toolName): string
    {
        return ReservationToolCatalog::get(ReservationToolName::from($toolName))->riskLevel()->value;
    }
}
