<?php

declare(strict_types=1);

namespace DressnMore\Aos\Planner\Architecture;

/**
 * ADR: Sprint 6 AI Planner scope.
 *
 * In scope: intent/goal/task planning, execution plan generation, clarification/escalation.
 * Out of scope: OpenAI/LLM, Tool execution, WhatsApp, Laravel models, DB, APIs.
 */
final class PlannerScopeDecision
{
    public const SPRINT = 'AI Planner';

    public const VERSION = '0.6.0';

    /**
     * @return list<string>
     */
    public static function excludedConcerns(): array
    {
        return [
            'openai',
            'llm_providers',
            'tool_execution',
            'whatsapp',
            'channels',
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
            'dressnmore/aos-planner',
        ];
    }
}
