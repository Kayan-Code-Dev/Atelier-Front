<?php

declare(strict_types=1);

namespace DressnMore\SmartAssistant\Domain\Policy;

/**
 * Architecture-level policy identifiers (enforcement in later sprints).
 */
final class PolicyCatalog
{
    public const TENANT_ISOLATION = 'tenant_isolation';
    public const AGENT_ISOLATION = 'agent_isolation';
    public const CHANNEL_ISOLATION = 'channel_isolation';
    public const PERMISSION_ENFORCEMENT = 'permission_enforcement';
    public const SUBSCRIPTION_ENFORCEMENT = 'subscription_enforcement';
    public const DATA_PRIVACY = 'data_privacy';
    public const AUDIT_LOGGING = 'audit_logging';

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            self::TENANT_ISOLATION,
            self::AGENT_ISOLATION,
            self::CHANNEL_ISOLATION,
            self::PERMISSION_ENFORCEMENT,
            self::SUBSCRIPTION_ENFORCEMENT,
            self::DATA_PRIVACY,
            self::AUDIT_LOGGING,
        ];
    }
}
