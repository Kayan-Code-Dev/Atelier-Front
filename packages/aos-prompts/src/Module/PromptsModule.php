<?php

declare(strict_types=1);

namespace DressnMore\Aos\Prompts\Module;

use DressnMore\Aos\Core\Module\AbstractModule;
use DressnMore\Aos\Prompts\Application\PromptEngine;

final class PromptsModule extends AbstractModule
{
    public function __construct(
        private readonly PromptEngine $engine,
    ) {}

    public function name(): string
    {
        return $this->assertName('aos.prompts');
    }

    public function title(): string
    {
        return 'AOS Prompt Engine';
    }

    public function version(): string
    {
        return '0.7.0';
    }

    public function isHealthy(): bool
    {
        return $this->engine instanceof PromptEngine;
    }
}
