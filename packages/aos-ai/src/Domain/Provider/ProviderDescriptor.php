<?php

declare(strict_types=1);

namespace DressnMore\Aos\Ai\Domain\Provider;

use DressnMore\Aos\Ai\Domain\Capability\ModelCapability;
use DressnMore\Aos\Ai\Domain\Health\HealthStatus;

/**
 * Immutable provider registry entry (plugin metadata).
 */
final class ProviderDescriptor
{
    /**
     * @param  list<ModelCapability>  $capabilities
     * @param  list<string>  $modelIds
     */
    public function __construct(
        private readonly ProviderId $id,
        private readonly ProviderKind $kind,
        private readonly string $name,
        private readonly string $version,
        private readonly array $capabilities,
        private readonly array $modelIds = [],
        private readonly int $priority = 100,
        private readonly bool $enabled = true,
        private readonly HealthStatus $health = HealthStatus::Healthy,
        private readonly float $budgetWeight = 1.0,
    ) {}

    public function id(): ProviderId
    {
        return $this->id;
    }

    public function kind(): ProviderKind
    {
        return $this->kind;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function version(): string
    {
        return $this->version;
    }

    /** @return list<ModelCapability> */
    public function capabilities(): array
    {
        return $this->capabilities;
    }

    /** @return list<string> */
    public function modelIds(): array
    {
        return $this->modelIds;
    }

    public function priority(): int
    {
        return $this->priority;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function health(): HealthStatus
    {
        return $this->health;
    }

    public function budgetWeight(): float
    {
        return $this->budgetWeight;
    }

    /**
     * @param  list<ModelCapability>  $required
     */
    public function supportsAll(array $required): bool
    {
        foreach ($required as $capability) {
            $found = false;
            foreach ($this->capabilities as $cap) {
                if ($cap === $capability) {
                    $found = true;
                    break;
                }
            }
            if (! $found) {
                return false;
            }
        }

        return true;
    }

    public function withHealth(HealthStatus $health): self
    {
        return new self(
            $this->id,
            $this->kind,
            $this->name,
            $this->version,
            $this->capabilities,
            $this->modelIds,
            $this->priority,
            $this->enabled,
            $health,
            $this->budgetWeight,
        );
    }

    public function withEnabled(bool $enabled): self
    {
        return new self(
            $this->id,
            $this->kind,
            $this->name,
            $this->version,
            $this->capabilities,
            $this->modelIds,
            $this->priority,
            $enabled,
            $this->health,
            $this->budgetWeight,
        );
    }
}
