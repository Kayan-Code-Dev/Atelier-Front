<?php

declare(strict_types=1);

namespace DressnMore\Aos\Permissions\Architecture;

/**
 * ADR: Sprint 5 Permission & Policy Engine scope.
 *
 * In scope: authorization pipeline, capabilities, permissions, policies, risk, approval, modes.
 * Out of scope: OpenAI, Planner, Prompt, channels, business tool implementations, DB/Eloquent, APIs.
 */
final class PermissionsScopeDecision
{
    public const SPRINT = 'Permission & Policy Engine';

    public const VERSION = '0.5.0';

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
            'business_tool_implementations',
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
            'dressnmore/aos-permissions',
        ];
    }
}
