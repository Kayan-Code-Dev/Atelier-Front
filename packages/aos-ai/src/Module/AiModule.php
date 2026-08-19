<?php

declare(strict_types=1);

namespace DressnMore\Aos\Ai\Module;

use DressnMore\Aos\Ai\Application\AiEngine;
use DressnMore\Aos\Core\Module\AbstractModule;

final class AiModule extends AbstractModule
{
    public function __construct(
        private readonly AiEngine $engine,
    ) {}

    public function name(): string
    {
        return $this->assertName('aos.ai');
    }

    public function title(): string
    {
        return 'AOS AI Provider Platform';
    }

    public function version(): string
    {
        return '0.10.0';
    }

    public function isHealthy(): bool
    {
        return $this->engine instanceof AiEngine;
    }
}
