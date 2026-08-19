<?php

declare(strict_types=1);

namespace DressnMore\Aos\Context\Domain\Permission;

/**
 * Capability labels available to the digital employee for this cycle.
 */
final class AvailableCapabilities
{
    /**
     * @param  list<string>  $capabilities
     */
    public function __construct(
        private readonly array $capabilities,
    ) {}

    public static function empty(): self
    {
        return new self([]);
    }

    /**
     * @param  list<string>  $capabilities
     */
    public static function of(array $capabilities): self
    {
        return new self(array_values(array_unique($capabilities)));
    }

    /**
     * @return list<string>
     */
    public function all(): array
    {
        return $this->capabilities;
    }

    public function has(string $capability): bool
    {
        return in_array($capability, $this->capabilities, true);
    }
}
