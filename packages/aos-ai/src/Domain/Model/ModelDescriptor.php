<?php

declare(strict_types=1);

namespace DressnMore\Aos\Ai\Domain\Model;

use DressnMore\Aos\Ai\Domain\Capability\ModelCapability;
use DressnMore\Aos\Ai\Domain\Provider\ProviderId;

/**
 * Immutable model catalog entry.
 */
final class ModelDescriptor
{
    /**
     * @param  list<ModelCapability>  $capabilities
     */
    public function __construct(
        private readonly ModelId $id,
        private readonly ProviderId $providerId,
        private readonly string $name,
        private readonly string $version,
        private readonly array $capabilities,
        private readonly float $costPer1kInputTokens = 0.001,
        private readonly float $costPer1kOutputTokens = 0.002,
        private readonly int $maxContextTokens = 8192,
        private readonly int $typicalLatencyMs = 800,
        private readonly bool $enabled = true,
    ) {}

    public function id(): ModelId
    {
        return $this->id;
    }

    public function providerId(): ProviderId
    {
        return $this->providerId;
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

    public function supports(ModelCapability $capability): bool
    {
        foreach ($this->capabilities as $cap) {
            if ($cap === $capability) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<ModelCapability>  $required
     */
    public function supportsAll(array $required): bool
    {
        foreach ($required as $capability) {
            if (! $this->supports($capability)) {
                return false;
            }
        }

        return true;
    }

    public function costPer1kInputTokens(): float
    {
        return $this->costPer1kInputTokens;
    }

    public function costPer1kOutputTokens(): float
    {
        return $this->costPer1kOutputTokens;
    }

    public function maxContextTokens(): int
    {
        return $this->maxContextTokens;
    }

    public function typicalLatencyMs(): int
    {
        return $this->typicalLatencyMs;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }
}
