<?php

declare(strict_types=1);

namespace DressnMore\Aos\Core\Module;

use DressnMore\Aos\Core\Module\AbstractModule;

/**
 * Built-in core module marker for the foundation kernel package.
 */
final class CoreModule extends AbstractModule
{
    public function name(): string
    {
        return $this->assertName('aos.core');
    }

    public function title(): string
    {
        return 'AOS Core';
    }

    public function version(): string
    {
        return '0.1.0';
    }
}
