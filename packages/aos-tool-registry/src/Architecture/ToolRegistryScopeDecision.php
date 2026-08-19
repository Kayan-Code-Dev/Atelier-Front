<?php

declare(strict_types=1);

namespace DressnMore\Aos\ToolRegistry\Architecture;

/**
 * Sprint 16 scope: discovery & registration platform only.
 */
final class ToolRegistryScopeDecision
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
            'tool_execution_implementations',
            'domain_service_implementations',
        ];
    }

    /**
     * @return list<string>
     */
    public static function complements(): array
    {
        return [
            'dressnmore/aos-tools (Tool Gateway / Manifest registry — execution plane)',
            'dressnmore/customer-binding',
            'dressnmore/reservation-binding',
        ];
    }
}
