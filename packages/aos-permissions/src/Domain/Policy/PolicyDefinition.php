<?php

declare(strict_types=1);

namespace DressnMore\Aos\Permissions\Domain\Policy;

use DressnMore\Aos\Permissions\Domain\Capability\CapabilityCode;
use DressnMore\Aos\Permissions\Domain\Decision\AuthorizationOutcome;
use DressnMore\Aos\Permissions\Domain\Mode\OperatingModeCode;
use DressnMore\Aos\Permissions\Domain\Risk\RiskLevel;

/**
 * Declarative policy rule — no DressnMore business logic, only authorization constraints.
 */
final class PolicyDefinition
{
    /**
     * @param  list<string>  $appliesToCapabilities  empty = all
     * @param  list<string>  $appliesToModes  empty = all
     */
    public function __construct(
        private readonly PolicyId $id,
        private readonly PolicyType $type,
        private readonly string $name,
        private readonly AuthorizationOutcome $effect,
        private readonly int $priority = 100,
        private readonly ?RiskLevel $minimumRisk = null,
        private readonly array $appliesToCapabilities = [],
        private readonly array $appliesToModes = [],
        private readonly bool $enabled = true,
    ) {}

    public function id(): PolicyId
    {
        return $this->id;
    }

    public function type(): PolicyType
    {
        return $this->type;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function effect(): AuthorizationOutcome
    {
        return $this->effect;
    }

    public function priority(): int
    {
        return $this->priority;
    }

    public function minimumRisk(): ?RiskLevel
    {
        return $this->minimumRisk;
    }

    /**
     * @return list<string>
     */
    public function appliesToCapabilities(): array
    {
        return $this->appliesToCapabilities;
    }

    /**
     * @return list<string>
     */
    public function appliesToModes(): array
    {
        return $this->appliesToModes;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function matches(CapabilityCode $capability, OperatingModeCode $mode, RiskLevel $risk): bool
    {
        if (! $this->enabled) {
            return false;
        }

        if ($this->appliesToCapabilities !== []
            && ! in_array($capability->toString(), $this->appliesToCapabilities, true)
        ) {
            return false;
        }

        if ($this->appliesToModes !== []
            && ! in_array($mode->toString(), $this->appliesToModes, true)
        ) {
            return false;
        }

        if ($this->minimumRisk !== null && ! $risk->atLeast($this->minimumRisk)) {
            return false;
        }

        return true;
    }
}
