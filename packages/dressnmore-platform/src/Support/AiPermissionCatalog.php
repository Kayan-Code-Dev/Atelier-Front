<?php

declare(strict_types=1);

namespace DressnMore\Platform\Support;

/**
 * Canonical AI RBAC keys for DressnMore tenants (Sprint 18A).
 */
final class AiPermissionCatalog
{
    /**
     * @return array<string, string> key => Arabic label
     */
    public static function definitions(): array
    {
        return [
            'ai.access' => 'الوصول للمستشار الذكي',
            'ai.chat' => 'محادثة المستشار الذكي',
            'ai.history' => 'سجل محادثات المستشار الذكي',
            'ai.memory' => 'ذاكرة المستشار الذكي',
            'ai.integrations' => 'تكاملات المستشار الذكي',
            'ai.settings' => 'إعدادات المستشار الذكي',
            'ai.usage' => 'استخدام المستشار الذكي',
        ];
    }

    /**
     * @return list<string>
     */
    public static function keys(): array
    {
        return array_keys(self::definitions());
    }
}
