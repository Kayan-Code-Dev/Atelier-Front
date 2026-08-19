<?php

declare(strict_types=1);

namespace DressnMore\CustomerBinding\Application;

use DressnMore\CustomerBinding\Contracts\CustomerPolicyAdapterInterface;
use DressnMore\CustomerBinding\Domain\Tools\ApprovalPolicy;
use DressnMore\CustomerBinding\Domain\Tools\CustomerToolCatalog;
use DressnMore\CustomerBinding\Domain\Tools\CustomerToolName;

final class CustomerPolicyAdapter implements CustomerPolicyAdapterInterface
{
    public function requiresApproval(string $toolName): bool
    {
        $contract = CustomerToolCatalog::get(CustomerToolName::from($toolName));

        return in_array($contract->approvalPolicy(), [ApprovalPolicy::Often, ApprovalPolicy::Always], true);
    }

    public function riskLevel(string $toolName): string
    {
        return CustomerToolCatalog::get(CustomerToolName::from($toolName))->riskLevel()->value;
    }
}
