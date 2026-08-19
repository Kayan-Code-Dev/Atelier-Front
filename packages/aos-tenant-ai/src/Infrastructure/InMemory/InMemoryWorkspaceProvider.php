<?php

declare(strict_types=1);

namespace DressnMore\Aos\TenantAi\Infrastructure\InMemory;

use DressnMore\Aos\TenantAi\Contracts\WorkspaceProviderInterface;
use DressnMore\Aos\TenantAi\Domain\Workspace\AiWorkspace;

/** Test/demo only — not a production store. */
final class InMemoryWorkspaceProvider implements WorkspaceProviderInterface
{
    /** @var array<string, AiWorkspace> */
    private array $byTenant = [];

    public function create(string $tenantId, string $workspaceId): AiWorkspace
    {
        $workspace = new AiWorkspace($workspaceId, $tenantId);
        $this->byTenant[$tenantId] = $workspace;

        return $workspace;
    }

    public function find(string $tenantId): ?AiWorkspace
    {
        return $this->byTenant[$tenantId] ?? null;
    }

    public function update(AiWorkspace $workspace): AiWorkspace
    {
        $this->byTenant[$workspace->tenantId()] = $workspace;

        return $workspace;
    }
}
