<?php

declare(strict_types=1);

namespace DressnMore\Aos\Response\Module;

use DressnMore\Aos\Core\Module\AbstractModule;
use DressnMore\Aos\Response\Contracts\ResponseEngineInterface;

final class ResponseModule extends AbstractModule
{
    public function __construct(
        private readonly ResponseEngineInterface $engine,
    ) {}

    public function name(): string
    {
        return $this->assertName('aos.response');
    }

    public function title(): string
    {
        return 'AOS AI Response Engine';
    }

    public function version(): string
    {
        return '0.20.0';
    }

    public function isHealthy(): bool
    {
        return $this->engine instanceof ResponseEngineInterface;
    }
}
