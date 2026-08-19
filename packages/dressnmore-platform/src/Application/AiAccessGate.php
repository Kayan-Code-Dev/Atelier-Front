<?php

declare(strict_types=1);

namespace DressnMore\Platform\Application;

use App\Models\Central\Tenant;
use App\Support\PlanFeatureGate;
use DressnMore\Platform\Domain\AiNavigation;

/**
 * Resolves AI Assistant visibility across global / package / tenant / RBAC layers.
 * Does not call Planner, Gateway, or LLM.
 */
final class AiAccessGate
{
    public function __construct(
        private readonly PlanFeatureGate $planFeatureGate,
    ) {}

    public function isModuleEnabled(): bool
    {
        $modules = config('aos.enabled_modules', []);
        $moduleOn = is_array($modules)
            ? (bool) ($modules['platform.ai-integration'] ?? true)
            : true;

        return $moduleOn
            && (bool) config('aos.feature_flags.ai_platform_integration', true)
            && (bool) config('dressnmore-platform.ai.enabled_globally', true);
    }

    public function isPackageEnabled(Tenant $tenant): bool
    {
        $feature = (string) config('dressnmore-platform.ai.plan_feature', 'ai_assistant.enabled');

        return $this->planFeatureGate->isEnabled($tenant, $feature);
    }

    public function isTenantEnabled(Tenant $tenant): bool
    {
        $disabled = config('dressnmore-platform.ai.tenant_disabled', []);
        if (! is_array($disabled)) {
            return true;
        }

        $ids = array_map('strval', $disabled);

        return ! in_array((string) $tenant->id, $ids, true)
            && ! in_array((string) ($tenant->slug ?? ''), $ids, true);
    }

    /**
     * Sidebar / feature visibility (module + package + tenant). Permissions checked separately.
     */
    public function isFeatureVisible(Tenant $tenant): bool
    {
        return $this->isModuleEnabled()
            && $this->isPackageEnabled($tenant)
            && $this->isTenantEnabled($tenant);
    }

    /**
     * Full access gate for HTTP requests.
     *
     * @param list<string> $userPermissions
     */
    public function canAccess(Tenant $tenant, array $userPermissions, string $requiredPermission = 'ai.access'): bool
    {
        if (! $this->isFeatureVisible($tenant)) {
            return false;
        }

        if (in_array('*', $userPermissions, true)) {
            return true;
        }

        return $this->hasAiPermission($userPermissions, $requiredPermission);
    }

    /**
     * @param list<string> $userPermissions
     */
    public function hasAiPermission(array $userPermissions, ?string $requiredPermission = null): bool
    {
        if (in_array('*', $userPermissions, true)) {
            return true;
        }

        if (in_array('ai.access', $userPermissions, true)) {
            return true;
        }

        if ($requiredPermission !== null && in_array($requiredPermission, $userPermissions, true)) {
            return true;
        }

        // Legacy Intelligence module permissions (pre Sprint 18A).
        return in_array('intelligence.view', $userPermissions, true)
            || in_array('intelligence.chat', $userPermissions, true);
    }

    /**
     * @param list<string> $userPermissions
     * @return array<string, mixed>
     */
    public function navigationPayload(Tenant $tenant, array $userPermissions): array
    {
        $visible = $this->isFeatureVisible($tenant);
        $hasAnyAiPerm = $this->hasAiPermission($userPermissions);

        $items = ($visible && $hasAnyAiPerm)
            ? AiNavigation::forPermissions($userPermissions)
            : [];

        return [
            'module' => 'platform.ai-integration',
            'label' => config('dressnmore-platform.ai.display_name'),
            'label_ar' => config('dressnmore-platform.ai.display_name_ar'),
            'icon' => config('dressnmore-platform.ai.icon'),
            'visible' => $visible && $items !== [],
            'path' => '/tenant/ai',
            'items' => $items,
            'gates' => [
                'module' => $this->isModuleEnabled(),
                'package' => $this->isPackageEnabled($tenant),
                'tenant' => $this->isTenantEnabled($tenant),
            ],
        ];
    }
}
