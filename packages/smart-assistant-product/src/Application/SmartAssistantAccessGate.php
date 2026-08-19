<?php

declare(strict_types=1);

namespace DressnMore\SmartAssistantProduct\Application;

use App\Models\Central\Tenant;
use App\Support\PlanFeatureGate;
use DressnMore\SmartAssistantProduct\Domain\SmartAssistantNavigation;

/**
 * Visibility gate: global → AOS flag → plan → tenant denylist → RBAC.
 */
final class SmartAssistantAccessGate
{
    public function __construct(
        private readonly PlanFeatureGate $planFeatureGate,
    ) {}

    public function isModuleEnabled(): bool
    {
        $modules = config('aos.enabled_modules', []);
        $moduleOn = is_array($modules)
            ? (bool) ($modules['platform.smart-assistant'] ?? true)
            : true;

        return $moduleOn
            && (bool) config('aos.feature_flags.smart_assistant_product', true)
            && (bool) config('smart-assistant-product.enabled_globally', true);
    }

    public function isPackageEnabled(Tenant $tenant): bool
    {
        $feature = (string) config('smart-assistant-product.plan_feature', 'smart_assistant.enabled');

        return $this->planFeatureGate->isEnabled($tenant, $feature);
    }

    public function isTenantEnabled(Tenant $tenant): bool
    {
        $disabled = config('smart-assistant-product.tenant_disabled', []);
        if (! is_array($disabled)) {
            return true;
        }

        $ids = array_map('strval', $disabled);

        return ! in_array((string) $tenant->id, $ids, true)
            && ! in_array((string) ($tenant->slug ?? ''), $ids, true);
    }

    public function isFeatureVisible(Tenant $tenant): bool
    {
        return $this->isModuleEnabled()
            && $this->isPackageEnabled($tenant)
            && $this->isTenantEnabled($tenant);
    }

    /**
     * @param list<string> $userPermissions
     */
    public function canAccess(Tenant $tenant, array $userPermissions, string $required = 'smart_assistant.access'): bool
    {
        if (! $this->isFeatureVisible($tenant)) {
            return false;
        }

        if (in_array('*', $userPermissions, true) || in_array('smart_assistant.access', $userPermissions, true)) {
            return true;
        }

        return in_array($required, $userPermissions, true);
    }

    /**
     * @param list<string> $userPermissions
     * @return array<string, mixed>
     */
    public function navigationPayload(Tenant $tenant, array $userPermissions): array
    {
        $visible = $this->isFeatureVisible($tenant);
        $hasAccess = in_array('*', $userPermissions, true)
            || in_array('smart_assistant.access', $userPermissions, true)
            || count(array_intersect($userPermissions, SmartAssistantNavigation::permissionKeys())) > 0;

        $items = ($visible && $hasAccess)
            ? SmartAssistantNavigation::forPermissions($userPermissions)
            : [];

        return [
            'module' => 'platform.smart-assistant',
            'label' => config('smart-assistant-product.display_name'),
            'label_ar' => config('smart-assistant-product.display_name_ar'),
            'icon' => config('smart-assistant-product.icon'),
            'visible' => $visible && $items !== [],
            'path' => '/tenant/smart-assistant',
            'items' => $items,
            'gates' => [
                'module' => $this->isModuleEnabled(),
                'package' => $this->isPackageEnabled($tenant),
                'tenant' => $this->isTenantEnabled($tenant),
            ],
        ];
    }
}
