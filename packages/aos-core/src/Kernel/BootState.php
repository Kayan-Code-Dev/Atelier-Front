<?php

declare(strict_types=1);

namespace DressnMore\Aos\Core\Kernel;

/**
 * Ordered boot states for the AOS foundation kernel.
 */
enum BootState: string
{
    case Idle = 'idle';
    case LoadConfig = 'load_config';
    case RegisterModules = 'register_modules';
    case RegisterContracts = 'register_contracts';
    case RegisterProviders = 'register_providers';
    case RegisterEventBus = 'register_event_bus';
    case RegisterObservability = 'register_observability';
    case HealthCheck = 'health_check';
    case Ready = 'ready';
    case Failed = 'failed';
}
