<?php

declare(strict_types=1);

namespace DressnMore\Aos\Planner\Module;

use DressnMore\Aos\Core\Module\AbstractModule;
use DressnMore\Aos\Planner\Application\PlannerEngine;

final class PlannerModule extends AbstractModule
{
    public function __construct(
        private readonly PlannerEngine $engine,
    ) {}

    public function name(): string
    {
        return $this->assertName('aos.planner');
    }

    public function title(): string
    {
        return 'AOS AI Planner';
    }

    public function version(): string
    {
        return '0.18.0';
    }

    public function isHealthy(): bool
    {
        return $this->engine instanceof PlannerEngine;
    }
}
