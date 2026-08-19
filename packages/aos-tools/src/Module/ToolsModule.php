<?php

declare(strict_types=1);

namespace DressnMore\Aos\Tools\Module;

use DressnMore\Aos\Core\Module\AbstractModule;
use DressnMore\Aos\Tools\Domain\Registry\ToolRegistryInterface;

final class ToolsModule extends AbstractModule
{
    public function __construct(
        private readonly ToolRegistryInterface $registry,
    ) {}

    public function name(): string
    {
        return $this->assertName('aos.tools');
    }

    public function title(): string
    {
        return 'AOS Business Tool Gateway';
    }

    public function version(): string
    {
        return '0.4.0';
    }

    public function isHealthy(): bool
    {
        return $this->registry instanceof ToolRegistryInterface;
    }
}
