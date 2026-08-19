<?php

declare(strict_types=1);

namespace DressnMore\SmartAssistantProduct\Domain;

final class SmartAssistantNavigation
{
    /**
     * @return list<array{key:string,label:string,label_ar:string,path:string,permission:string}>
     */
    public static function items(): array
    {
        return [
            [
                'key' => 'dashboard',
                'label' => 'Dashboard',
                'label_ar' => 'لوحة المساعد',
                'path' => '/tenant/smart-assistant',
                'permission' => 'smart_assistant.access',
            ],
            [
                'key' => 'channels',
                'label' => 'Channels',
                'label_ar' => 'القنوات',
                'path' => '/tenant/smart-assistant/channels',
                'permission' => 'smart_assistant.channels',
            ],
            [
                'key' => 'messages',
                'label' => 'Messages',
                'label_ar' => 'الرسائل',
                'path' => '/tenant/smart-assistant/messages',
                'permission' => 'smart_assistant.messages',
            ],
            [
                'key' => 'comments',
                'label' => 'Comments',
                'label_ar' => 'التعليقات',
                'path' => '/tenant/smart-assistant/comments',
                'permission' => 'smart_assistant.comments',
            ],
            [
                'key' => 'automations',
                'label' => 'Automations',
                'label_ar' => 'الأتمتة',
                'path' => '/tenant/smart-assistant/automations',
                'permission' => 'smart_assistant.automations',
            ],
            [
                'key' => 'settings',
                'label' => 'Settings',
                'label_ar' => 'الإعدادات',
                'path' => '/tenant/smart-assistant/settings',
                'permission' => 'smart_assistant.settings',
            ],
        ];
    }

    /**
     * @return list<string>
     */
    public static function permissionKeys(): array
    {
        return array_column(self::items(), 'permission');
    }

    /**
     * @param list<string> $granted
     * @return list<array{key:string,label:string,label_ar:string,path:string,permission:string}>
     */
    public static function forPermissions(array $granted): array
    {
        $unlockAll = in_array('*', $granted, true)
            || in_array('smart_assistant.access', $granted, true);

        $out = [];
        foreach (self::items() as $item) {
            if ($unlockAll || in_array($item['permission'], $granted, true)) {
                $out[] = $item;
            }
        }

        return $out;
    }
}
