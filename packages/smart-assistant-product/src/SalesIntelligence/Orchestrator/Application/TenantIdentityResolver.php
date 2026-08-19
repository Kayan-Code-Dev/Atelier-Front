<?php

declare(strict_types=1);

namespace DressnMore\SmartAssistantProduct\SalesIntelligence\Orchestrator\Application;

use App\Models\Central\Tenant;
use App\Models\Tenant\Branch;
use App\Models\Tenant\User;
use App\Models\Tenant\WebsiteSite;
use App\Services\Tenant\TenantContext;
use App\Services\Tenant\TenantDatabaseManager;
use DressnMore\SmartAssistantProduct\Models\SmartAssistantAgentSettings;
use DressnMore\SmartAssistantProduct\SalesIntelligence\Orchestrator\Domain\TenantIdentity;
use Throwable;

/**
 * Resolves tenant + agent persona from the WhatsApp connection tenant_id.
 * Atelier business name comes from the tenant panel (website / owner profile),
 * never from the admin-console tenants.name when a panel name exists.
 */
final class TenantIdentityResolver
{
    public function resolve(int $tenantId, ?string $agentNameOverride = null, ?string $departmentName = null): TenantIdentity
    {
        $tenant = Tenant::query()->find($tenantId);
        $settings = SmartAssistantAgentSettings::forTenant($tenantId);
        $platformId = (int) config('smart-assistant-product.whatsapp_web.platform_tenant_id', 0);
        $isPlatform = $platformId > 0 && $platformId === $tenantId;

        $businessName = $isPlatform
            ? 'DressnMore'
            : $this->resolveAtelierBusinessName($tenant);

        $agentName = $agentNameOverride !== null && trim($agentNameOverride) !== ''
            ? trim($agentNameOverride)
            : $this->resolvedAgentName($settings);

        return new TenantIdentity(
            tenantId: $tenantId,
            isPlatform: $isPlatform,
            businessName: $businessName !== '' ? $businessName : ($isPlatform ? 'DressnMore' : 'الأتيليه'),
            agentName: $agentName,
            agentRole: (string) ($settings->role ?: 'sales'),
            tone: (string) ($settings->tone ?: 'friendly'),
            style: (string) ($settings->style ?: 'conversational'),
            language: (string) ($settings->language ?: 'ar'),
            personality: filled($settings->personality) ? (string) $settings->personality : null,
            businessInstructions: filled($settings->business_instructions) ? (string) $settings->business_instructions : null,
            welcomeMessage: filled($settings->welcome_message) ? (string) $settings->welcome_message : null,
            handoffMessage: filled($settings->handoff_message) ? (string) $settings->handoff_message : null,
            departmentName: $departmentName !== null && trim($departmentName) !== '' ? trim($departmentName) : null,
        );
    }

    private function resolveAtelierBusinessName(?Tenant $tenant): string
    {
        $adminName = trim((string) ($tenant?->name ?: $tenant?->slug ?: ''));
        if ($tenant === null) {
            return $adminName !== '' ? $adminName : 'الأتيليه';
        }

        try {
            app(TenantDatabaseManager::class)->connect($tenant);
            app(TenantContext::class)->setTenant($tenant);
        } catch (Throwable) {
            return $adminName !== '' ? $adminName : 'الأتيليه';
        }

        $panelName = $this->readTenantPanelBusinessName($adminName);
        if ($this->isUsableBusinessName($panelName)) {
            return $panelName;
        }

        return $adminName !== '' ? $adminName : 'الأتيليه';
    }

    /**
     * Tenant-panel sources, in order:
     * 1) Website "اسم الموقع" when the atelier actually set it (not the admin default)
     * 2) Owner / first user name from الملف الشخصي
     * 3) First branch name
     */
    private function readTenantPanelBusinessName(string $adminName): string
    {
        try {
            $site = WebsiteSite::query()->orderBy('id')->first();
            if ($site !== null) {
                $general = is_array($site->general) ? $site->general : [];
                $siteName = $this->cleanName((string) ($general['site_name'] ?? ''));
                if ($this->isUsableBusinessName($siteName) && ! $this->sameName($siteName, $adminName)) {
                    return $siteName;
                }
            }
        } catch (Throwable) {
            // website module may be absent on older tenant DBs
        }

        try {
            $ownerName = $this->cleanName((string) (User::query()
                ->whereHas('roles', static fn ($q) => $q->where('slug', 'owner'))
                ->orderBy('id')
                ->value('name') ?: ''));
            if ($this->isUsableBusinessName($ownerName)) {
                return $ownerName;
            }

            $firstUser = $this->cleanName((string) (User::query()->orderBy('id')->value('name') ?: ''));
            if ($this->isUsableBusinessName($firstUser)) {
                return $firstUser;
            }
        } catch (Throwable) {
            //
        }

        try {
            $branch = $this->cleanName((string) (Branch::query()->orderBy('id')->value('name') ?: ''));
            if ($this->isUsableBusinessName($branch) && ! $this->sameName($branch, $adminName)) {
                return $branch;
            }
        } catch (Throwable) {
            //
        }

        return '';
    }

    private function resolvedAgentName(SmartAssistantAgentSettings $settings): ?string
    {
        if (filled($settings->display_name)) {
            return trim((string) $settings->display_name);
        }
        if (filled($settings->assistant_name)) {
            return trim((string) $settings->assistant_name);
        }

        return null;
    }

    private function cleanName(string $name): string
    {
        return trim(preg_replace('/\s+/u', ' ', $name) ?? $name);
    }

    private function isUsableBusinessName(string $name): bool
    {
        if ($name === '') {
            return false;
        }

        $normalized = mb_strtolower($name);

        return ! in_array($normalized, [
            'dressnmore',
            'الأتيليه',
            'موقع الأتيليه',
            'atelier',
        ], true);
    }

    private function sameName(string $a, string $b): bool
    {
        return mb_strtolower(trim($a)) === mb_strtolower(trim($b));
    }
}
