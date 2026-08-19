<?php

declare(strict_types=1);

namespace DressnMore\Aos\ToolRegistry\Module;

use DressnMore\Aos\Core\Module\AbstractModule;
use DressnMore\Aos\ToolRegistry\Contracts\ToolRegistryInterface;

final class ToolRegistryModule extends AbstractModule
{
    public function __construct(private readonly ToolRegistryInterface $registry) {}

    public function name(): string
    {
        return $this->assertName('aos.tool-registry');
    }

    public function title(): string
    {
        return 'AOS AI Tool Registry & Capability Platform';
    }

    public function version(): string
    {
        return '0.16.0';
    }

    public function isHealthy(): bool
    {
        return $this->registry instanceof ToolRegistryInterface;
    }
}
