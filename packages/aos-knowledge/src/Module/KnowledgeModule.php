<?php

declare(strict_types=1);

namespace DressnMore\Aos\Knowledge\Module;

use DressnMore\Aos\Core\Module\AbstractModule;
use DressnMore\Aos\Knowledge\Application\KnowledgeEngine;

final class KnowledgeModule extends AbstractModule
{
    public function __construct(
        private readonly KnowledgeEngine $engine,
    ) {}

    public function name(): string
    {
        return $this->assertName('aos.knowledge');
    }

    public function title(): string
    {
        return 'AOS Knowledge Engine';
    }

    public function version(): string
    {
        return '0.9.0';
    }

    public function isHealthy(): bool
    {
        return $this->engine instanceof KnowledgeEngine;
    }
}
