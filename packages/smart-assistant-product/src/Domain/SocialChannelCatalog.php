<?php

declare(strict_types=1);

namespace DressnMore\SmartAssistantProduct\Domain;

final class SocialChannelCatalog
{
    public const WHATSAPP = 'whatsapp';
    public const FACEBOOK = 'facebook';
    public const INSTAGRAM = 'instagram';

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [self::WHATSAPP, self::FACEBOOK, self::INSTAGRAM];
    }

    public static function isValid(string $type): bool
    {
        return in_array($type, self::all(), true);
    }

    /**
     * @return array{enabled:bool,label:string,label_ar:string,supports:list<string>}|null
     */
    public static function definition(string $type): ?array
    {
        $channels = config('smart-assistant-product.channels', []);
        if (! is_array($channels) || ! isset($channels[$type]) || ! is_array($channels[$type])) {
            return null;
        }

        /** @var array{enabled?:bool,label?:string,label_ar?:string,supports?:list<string>} $def */
        $def = $channels[$type];

        return [
            'enabled' => (bool) ($def['enabled'] ?? true),
            'label' => (string) ($def['label'] ?? $type),
            'label_ar' => (string) ($def['label_ar'] ?? $type),
            'supports' => array_values($def['supports'] ?? []),
        ];
    }
}
