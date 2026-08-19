<?php

declare(strict_types=1);

namespace DressnMore\Platform\Domain;

/**
 * Tenant AI Assistant navigation IA (paths match Sprint 18A FE routes).
 */
final class AiNavigation
{
    /**
     * @return list<array{key:string,label:string,label_ar:string,path:string,permission:string}>
     */
    public static function items(): array
    {
        return [
            ['key' => 'chat', 'label' => 'Chat', 'label_ar' => 'المحادثة', 'path' => '/tenant/ai', 'permission' => 'ai.chat'],
            ['key' => 'history', 'label' => 'History', 'label_ar' => 'السجل', 'path' => '/tenant/ai/history', 'permission' => 'ai.history'],
            ['key' => 'settings', 'label' => 'Settings', 'label_ar' => 'الإعدادات', 'path' => '/tenant/ai/settings', 'permission' => 'ai.settings'],
            ['key' => 'memory', 'label' => 'Memory', 'label_ar' => 'الذاكرة', 'path' => '/tenant/ai/memory', 'permission' => 'ai.memory'],
            ['key' => 'integrations', 'label' => 'Integrations', 'label_ar' => 'التكاملات', 'path' => '/tenant/ai/integrations', 'permission' => 'ai.integrations'],
            ['key' => 'usage', 'label' => 'Usage', 'label_ar' => 'الاستخدام', 'path' => '/tenant/ai/usage', 'permission' => 'ai.usage'],
        ];
    }

    /**
     * @param list<string> $grantedPermissions
     * @return list<array{key:string,label:string,label_ar:string,path:string,permission:string}>
     */
    public static function forPermissions(array $grantedPermissions): array
    {
        $legacyIntelligence = in_array('intelligence.view', $grantedPermissions, true)
            || in_array('intelligence.chat', $grantedPermissions, true);

        $unlockAll = in_array('*', $grantedPermissions, true)
            || in_array('ai.access', $grantedPermissions, true)
            || $legacyIntelligence;

        $out = [];
        foreach (self::items() as $item) {
            if (
                $unlockAll
                || in_array($item['permission'], $grantedPermissions, true)
            ) {
                $out[] = $item;
            }
        }

        return $out;
    }
}
