<?php

declare(strict_types=1);

namespace DressnMore\Aos\TenantAi\Domain\Permission;

final class PermissionResolution
{
    /**
     * @param list<string> $permissions
     * @param list<string> $allowedCapabilities
     * @param list<string> $allowedTools
     */
    public function __construct(
        private readonly string $userId,
        private readonly string $role,
        private readonly array $permissions,
        private readonly array $allowedCapabilities,
        private readonly array $allowedTools,
    ) {}

    public function userId(): string { return $this->userId; }
    public function role(): string { return $this->role; }
    /** @return list<string> */
    public function permissions(): array { return $this->permissions; }
    /** @return list<string> */
    public function allowedCapabilities(): array { return $this->allowedCapabilities; }
    /** @return list<string> */
    public function allowedTools(): array { return $this->allowedTools; }

    public function allowsTool(string $toolName): bool
    {
        return in_array($toolName, $this->allowedTools, true);
    }

    public function allowsCapability(string $capability): bool
    {
        return in_array($capability, $this->allowedCapabilities, true);
    }
}
