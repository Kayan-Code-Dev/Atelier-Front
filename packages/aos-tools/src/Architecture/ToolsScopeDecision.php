<?php

declare(strict_types=1);

namespace DressnMore\Aos\Tools\Architecture;

/**
 * ADR: Sprint 4 Business Tool Gateway scope.
 *
 * In scope: registry, discovery, resolver, execution pipeline, contracts, hooks.
 * Out of scope: real DressnMore business tools, OpenAI, Planner, channels, DB/Eloquent, APIs.
 */
final class ToolsScopeDecision
{
    public const SPRINT = 'Business Tool Gateway';

    public const VERSION = '0.4.0';

    /**
     * @return list<string>
     */
    public static function excludedConcerns(): array
    {
        return [
            'openai',
            'planner',
            'prompt_engine',
            'whatsapp',
            'messenger',
            'instagram',
            'database',
            'eloquent',
            'controllers',
            'http_apis',
            'dressnmore_business_logic',
        ];
    }

    /**
     * @return list<string>
     */
    public static function includedPackages(): array
    {
        return [
            'dressnmore/aos-tools',
        ];
    }
}
