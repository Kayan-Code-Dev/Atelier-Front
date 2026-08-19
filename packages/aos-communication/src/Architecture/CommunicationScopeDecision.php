<?php

declare(strict_types=1);

namespace DressnMore\Aos\Communication\Architecture;

final class CommunicationScopeDecision
{
    /**
     * @return list<string>
     */
    public static function excludedConcerns(): array
    {
        return [
            'business_logic',
            'ai_logic',
            'prompt_logic',
            'planner_logic',
            'knowledge_logic',
            'sdk',
            'http_clients',
            'database',
            'laravel_models',
        ];
    }

    /**
     * @return list<string>
     */
    public static function includedPackages(): array
    {
        return [
            'dressnmore/aos-core',
            'dressnmore/aos-events',
            'dressnmore/aos-communication',
        ];
    }
}
