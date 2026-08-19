<?php

declare(strict_types=1);

namespace DressnMore\Aos\ToolRegistry\Application;

use DressnMore\Aos\ToolRegistry\Contracts\ToolRegistryInterface;
use DressnMore\Aos\ToolRegistry\Domain\Tool\ToolAvailability;
use DressnMore\Aos\ToolRegistry\Domain\Tool\ToolDescriptor;
use DressnMore\Aos\ToolRegistry\Domain\Tool\ToolMetadata;

/**
 * Tracks runtime availability overlays without mutating domain tool implementations.
 */
final class ToolAvailabilityManager
{
    /** @var array<string, ToolAvailability> */
    private array $overrides = [];

    public function __construct(private readonly ToolRegistryInterface $registry) {}

    public function mark(string $toolName, ToolAvailability $availability): void
    {
        $this->overrides[$toolName] = $availability;
    }

    public function availabilityOf(string $toolName): ?ToolAvailability
    {
        if (isset($this->overrides[$toolName])) {
            return $this->overrides[$toolName];
        }

        return $this->registry->get($toolName)?->metadata()->availability();
    }

    public function isAvailable(string $toolName): bool
    {
        $availability = $this->availabilityOf($toolName);

        return $availability === ToolAvailability::Available || $availability === ToolAvailability::Degraded;
    }

    /**
     * Re-publish descriptor with availability override for discovery consumers.
     */
    public function withAvailability(ToolDescriptor $descriptor): ToolDescriptor
    {
        $availability = $this->overrides[$descriptor->name()] ?? $descriptor->metadata()->availability();
        if ($availability === $descriptor->metadata()->availability()) {
            return $descriptor;
        }

        $meta = $descriptor->metadata();
        $updated = new ToolMetadata(
            $meta->toolName(),
            $meta->version(),
            $meta->ownerDomain(),
            $meta->description(),
            $meta->category(),
            $meta->capabilities(),
            $meta->riskLevel(),
            $meta->approvalRequirement(),
            $meta->operatingModes(),
            $meta->inputContract(),
            $meta->outputContract(),
            $meta->events(),
            $meta->dependencies(),
            $meta->status(),
            $availability,
            $meta->visibility(),
            $meta->tags(),
        );

        return new ToolDescriptor($updated, $descriptor->providerId(), $descriptor->permission());
    }
}
