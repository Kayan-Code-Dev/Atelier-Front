<?php

declare(strict_types=1);

namespace DressnMore\Aos\Permissions\Domain\Capability;

use DressnMore\Aos\Permissions\Domain\Risk\RiskLevel;

/**
 * Immutable capability definition registered in the Capability Registry.
 */
final class CapabilityDefinition
{
    /**
     * @param  list<string>  $requiredPermissions
     */
    public function __construct(
        private readonly CapabilityCode $code,
        private readonly string $description,
        private readonly RiskLevel $defaultRisk,
        private readonly array $requiredPermissions = [],
        private readonly bool $requiresApprovalByDefault = false,
    ) {}

    public function code(): CapabilityCode
    {
        return $this->code;
    }

    public function description(): string
    {
        return $this->description;
    }

    public function defaultRisk(): RiskLevel
    {
        return $this->defaultRisk;
    }

    /**
     * @return list<string>
     */
    public function requiredPermissions(): array
    {
        return $this->requiredPermissions;
    }

    public function requiresApprovalByDefault(): bool
    {
        return $this->requiresApprovalByDefault;
    }
}
