<?php

declare(strict_types=1);

namespace DressnMore\ReservationBinding\Architecture;

final class ReservationBindingScopeDecision
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
            'queries',
            'repository_implementations',
            'domain_service_implementations',
            'api',
            'http',
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
            'dressnmore/aos-tools',
            'dressnmore/reservation-binding',
        ];
    }
}
