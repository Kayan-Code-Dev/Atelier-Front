<?php

declare(strict_types=1);

namespace DressnMore\Aos\Workflow\Module;

use DressnMore\Aos\Core\Module\AbstractModule;
use DressnMore\Aos\Workflow\Application\WorkflowEngine;

final class WorkflowModule extends AbstractModule
{
    public function __construct(private readonly WorkflowEngine $engine) {}

    public function name(): string
    {
        return $this->assertName('aos.workflow');
    }

    public function title(): string
    {
        return 'AOS Workflow & Automation Engine';
    }

    public function version(): string
    {
        return '0.12.0';
    }

    public function isHealthy(): bool
    {
        return $this->engine instanceof WorkflowEngine;
    }
}
