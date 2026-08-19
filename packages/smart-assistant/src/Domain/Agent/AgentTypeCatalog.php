<?php

declare(strict_types=1);

namespace DressnMore\SmartAssistant\Domain\Agent;

final class AgentTypeCatalog
{
    public const SALES = 'sales';
    public const SUPPORT = 'support';
    public const MARKETING = 'marketing';
    public const SOCIAL = 'social';
    public const ANALYTICS = 'analytics';
    public const AUTOMATION = 'automation';
    public const CUSTOM = 'custom';

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            self::SALES,
            self::SUPPORT,
            self::MARKETING,
            self::SOCIAL,
            self::ANALYTICS,
            self::AUTOMATION,
            self::CUSTOM,
        ];
    }
}
