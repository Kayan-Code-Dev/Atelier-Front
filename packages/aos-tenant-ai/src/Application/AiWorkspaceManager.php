<?php

declare(strict_types=1);

namespace DressnMore\Aos\TenantAi\Application;

use DressnMore\Aos\TenantAi\Contracts\TenantAiEventPublisherInterface;
use DressnMore\Aos\TenantAi\Contracts\WorkspaceProviderInterface;
use DressnMore\Aos\TenantAi\Domain\Events\TenantAiDomainEvent;
use DressnMore\Aos\TenantAi\Domain\Policies\TenantAiPolicies;
use DressnMore\Aos\TenantAi\Domain\Workspace\AiWorkspace;

final class AiWorkspaceManager
{
    public function __construct(
        private readonly WorkspaceProviderInterface $workspaces,
        private readonly TenantAiPolicies $policies = new TenantAiPolicies(),
        private readonly ?TenantAiEventPublisherInterface $events = null,
    ) {}

    public function ensureForTenant(string $tenantId, ?string $workspaceId = null): AiWorkspace
    {
        $this->policies->assertTenantExists($tenantId);
        $existing = $this->workspaces->find($tenantId);
        if ($existing !== null) {
            return $existing;
        }

        $workspace = $this->workspaces->create($tenantId, $workspaceId ?? ('ws_'.$tenantId));
        $this->events?->publish(TenantAiDomainEvent::workspaceCreated([
            'tenantId' => $tenantId,
            'workspaceId' => $workspace->workspaceId(),
        ]));

        return $workspace;
    }

    public function getOrFail(string $tenantId): AiWorkspace
    {
        $this->policies->assertTenantExists($tenantId);
        $workspace = $this->workspaces->find($tenantId);
        $this->policies->assertWorkspaceExists($workspace !== null);

        return $workspace;
    }

    public function updateSettings(string $tenantId, array $settings): AiWorkspace
    {
        $workspace = $this->getOrFail($tenantId);
        $updated = $this->workspaces->update($workspace->withSettings($settings));
        $this->events?->publish(TenantAiDomainEvent::workspaceUpdated([
            'tenantId' => $tenantId,
            'workspaceId' => $updated->workspaceId(),
        ]));

        return $updated;
    }
}
