<?php

declare(strict_types=1);

namespace DressnMore\Aos\Workflow\Architecture;

final class WorkflowScopeDecision
{
    /**
     * @return list<string>
     */
    public static function excludedConcerns(): array
    {
        return [
            'database',
            'laravel_models',
            'business_logic',
            'controllers',
            'apis',
            'business_task_implementations',
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
            'dressnmore/aos-workflow',
        ];
    }
}
