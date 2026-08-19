<?php

declare(strict_types=1);

namespace DressnMore\SmartAssistant\Module;

use DressnMore\Aos\Core\Module\AbstractModule;
use DressnMore\SmartAssistant\Architecture\ArchitectureVersion;
use DressnMore\SmartAssistant\Contracts\Registry\AgentRegistryInterface;

final class SmartAssistantModule extends AbstractModule
{
    public function __construct(
        private readonly AgentRegistryInterface $agents,
    ) {}

    public function name(): string
    {
        return $this->assertName(ArchitectureVersion::MODULE);
    }

    public function title(): string
    {
        return 'Smart Assistant';
    }

    public function version(): string
    {
        return ArchitectureVersion::semver();
    }

    public function isHealthy(): bool
    {
        return ArchitectureVersion::isFrozen() && $this->agents instanceof AgentRegistryInterface;
    }

    /**
     * @return array<string, mixed>
     */
    public function metadata(): array
    {
        return [
            'key' => $this->name(),
            'alias' => 'smart-assistant',
            'package' => ArchitectureVersion::PACKAGE,
            'status' => ArchitectureVersion::STATUS,
            'version' => $this->version(),
        ];
    }
}
