<?php

declare(strict_types=1);

namespace DressnMore\Aos\Core\Module;

use DressnMore\Aos\Core\Module\Contracts\ModuleInterface;
use LogicException;

/**
 * Base module with shared metadata helpers.
 */
abstract class AbstractModule implements ModuleInterface
{
    public function register(): void
    {
        // Foundation modules override when they need container bindings.
    }

    public function boot(): void
    {
        // Foundation modules override when they need boot-time wiring.
    }

    public function isHealthy(): bool
    {
        return true;
    }

    protected function assertName(string $name): string
    {
        if ($name === '' || ! str_contains($name, '.')) {
            throw new LogicException('AOS module names must be non-empty and dotted (e.g. aos.core).');
        }

        return $name;
    }
}
