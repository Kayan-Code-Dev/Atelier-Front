<?php

declare(strict_types=1);

namespace DressnMore\Aos\TenantAi\Contracts;

use DressnMore\Aos\TenantAi\Domain\Workspace\AiWorkspace;

interface WorkspaceProviderInterface
{
    public function create(string $tenantId, string $workspaceId): AiWorkspace;

    public function find(string $tenantId): ?AiWorkspace;

    public function update(AiWorkspace $workspace): AiWorkspace;
}
