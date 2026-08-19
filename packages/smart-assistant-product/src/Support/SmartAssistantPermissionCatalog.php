<?php

declare(strict_types=1);

namespace DressnMore\SmartAssistantProduct\Support;

final class SmartAssistantPermissionCatalog
{
    /**
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return [
            'smart_assistant.access' => 'الوصول للمساعد الذكي',
            'smart_assistant.channels' => 'إدارة قنوات المساعد الذكي',
            'smart_assistant.messages' => 'رسائل المساعد الذكي',
            'smart_assistant.comments' => 'تعليقات المساعد الذكي',
            'smart_assistant.automations' => 'أتمتة المساعد الذكي',
            'smart_assistant.settings' => 'إعدادات المساعد الذكي',
        ];
    }

    /**
     * @return list<string>
     */
    public static function keys(): array
    {
        return array_keys(self::labels());
    }
}
