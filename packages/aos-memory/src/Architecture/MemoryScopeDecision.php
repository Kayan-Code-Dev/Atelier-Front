<?php

declare(strict_types=1);

namespace DressnMore\Aos\Memory\Architecture;

final class MemoryScopeDecision
{
    public const SPRINT = 'Memory Engine';

    public const VERSION = '0.8.0';

    /**
     * @return list<string>
     */
    public static function excludedConcerns(): array
    {
        return [
            'openai',
            'claude',
            'gemini',
            'ai_providers',
            'business_tools',
            'whatsapp',
            'messenger',
            'database',
            'eloquent',
            'controllers',
            'http_apis',
            'raw_message_persistence',
        ];
    }

    /**
     * @return list<string>
     */
    public static function includedPackages(): array
    {
        return [
            'dressnmore/aos-memory',
        ];
    }
}
