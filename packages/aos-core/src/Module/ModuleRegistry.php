<?php

declare(strict_types=1);

namespace DressnMore\Aos\Core\Module;

use DressnMore\Aos\Core\Module\Contracts\ModuleInterface;
use DressnMore\Aos\Core\Module\Contracts\ModuleRegistryInterface;
use InvalidArgumentException;

/**
 * In-memory module registry.
 */
final class ModuleRegistry implements ModuleRegistryInterface
{
    /** @var array<string, ModuleInterface> */
    private array $modules = [];

    public function add(ModuleInterface $module): void
    {
        $this->modules[$module->name()] = $module;
    }

    public function all(): array
    {
        return $this->modules;
    }

    public function has(string $name): bool
    {
        return isset($this->modules[$name]);
    }

    public function get(string $name): ModuleInterface
    {
        if (! $this->has($name)) {
            throw new InvalidArgumentException(sprintf('AOS module [%s] is not registered.', $name));
        }

        return $this->modules[$name];
    }

    public function names(): array
    {
        return array_keys($this->modules);
    }
}
