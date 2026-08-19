<?php

declare(strict_types=1);

namespace DressnMore\Aos\Ai\Architecture;

final class AiScopeDecision
{
    public const SPRINT = 'AI Provider Platform';

    public const VERSION = '0.10.0';

    /**
     * @return list<string>
     */
    public static function excludedConcerns(): array
    {
        return [
            'openai_sdk',
            'anthropic_sdk',
            'http_client',
            'guzzle',
            'curl',
            'database',
            'eloquent',
            'controllers',
            'http_apis',
            'real_network_calls',
        ];
    }

    /**
     * @return list<string>
     */
    public static function includedPackages(): array
    {
        return [
            'dressnmore/aos-ai',
        ];
    }
}
