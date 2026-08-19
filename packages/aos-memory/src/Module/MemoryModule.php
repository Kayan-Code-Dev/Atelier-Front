<?php

declare(strict_types=1);

namespace DressnMore\Aos\Memory\Module;

use DressnMore\Aos\Core\Module\AbstractModule;
use DressnMore\Aos\Memory\Application\MemoryEngine;

final class MemoryModule extends AbstractModule
{
    public function __construct(
        private readonly MemoryEngine $engine,
    ) {}

    public function name(): string
    {
        return $this->assertName('aos.memory');
    }

    public function title(): string
    {
        return 'AOS Memory Engine';
    }

    public function version(): string
    {
        return '0.8.0';
    }

    public function isHealthy(): bool
    {
        return $this->engine instanceof MemoryEngine;
    }
}
