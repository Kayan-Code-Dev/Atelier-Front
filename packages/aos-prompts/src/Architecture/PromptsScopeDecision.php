<?php

declare(strict_types=1);

namespace DressnMore\Aos\Prompts\Architecture;

final class PromptsScopeDecision
{
    public const SPRINT = 'Prompt Engine';

    public const VERSION = '0.7.0';

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
            'whatsapp',
            'messenger',
            'business_tool_implementations',
            'database',
            'eloquent',
            'controllers',
            'http_apis',
        ];
    }

    /**
     * @return list<string>
     */
    public static function includedPackages(): array
    {
        return [
            'dressnmore/aos-prompts',
        ];
    }
}
