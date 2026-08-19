<?php

declare(strict_types=1);

namespace DressnMore\Aos\ToolRegistry\Domain\Snapshot;

/**
 * Immutable registry snapshot for Planner / Prompt / Workspace / Analytics.
 */
final class RegistrySnapshot
{
    /**
     * @param list<array<string, mixed>> $tools
     * @param list<array<string, mixed>> $capabilities
     * @param list<array<string, mixed>> $intents
     * @param list<array<string, mixed>> $providers
     */
    public function __construct(
        private readonly string $generatedAt,
        private readonly array $tools,
        private readonly array $capabilities,
        private readonly array $intents,
        private readonly array $providers,
        private readonly int $toolCount,
        private readonly int $capabilityCount,
        private readonly int $intentCount,
    ) {}

    public function generatedAt(): string { return $this->generatedAt; }
    /** @return list<array<string, mixed>> */
    public function tools(): array { return $this->tools; }
    /** @return list<array<string, mixed>> */
    public function capabilities(): array { return $this->capabilities; }
    /** @return list<array<string, mixed>> */
    public function intents(): array { return $this->intents; }
    /** @return list<array<string, mixed>> */
    public function providers(): array { return $this->providers; }
    public function toolCount(): int { return $this->toolCount; }
    public function capabilityCount(): int { return $this->capabilityCount; }
    public function intentCount(): int { return $this->intentCount; }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'generatedAt' => $this->generatedAt,
            'toolCount' => $this->toolCount,
            'capabilityCount' => $this->capabilityCount,
            'intentCount' => $this->intentCount,
            'tools' => $this->tools,
            'capabilities' => $this->capabilities,
            'intents' => $this->intents,
            'providers' => $this->providers,
        ];
    }
}
