<?php

declare(strict_types=1);

namespace DressnMore\Aos\TenantAi\Architecture;

final class TenantAiScopeDecision
{
    /**
     * @return list<string>
     */
    public static function excludedConcerns(): array
    {
        return [
            'controllers',
            'routes',
            'database',
            'laravel_models',
            'http',
            'apis',
            'planner_execution',
            'gateway_execution',
            'tool_implementations',
            'workflow_engine',
            'smart_memory_implementations',
            'live_integrations',
            'llm_invocations',
        ];
    }

    /**
     * @return list<string>
     */
    public static function complements(): array
    {
        return [
            'dressnmore/aos-conversation',
            'dressnmore/aos-tool-registry',
            'dressnmore/aos-tools',
            'apps/ai-workspace (dashboard IA)',
        ];
    }
}
