<?php

declare(strict_types=1);

namespace DressnMore\Aos\Core\Module\Contracts;

/**
 * Registry of AOS foundation modules.
 */
interface ModuleRegistryInterface
{
    /**
     * Register a module instance (idempotent by name).
     */
    public function add(ModuleInterface $module): void;

    /**
     * @return array<string, ModuleInterface>
     */
    public function all(): array;

    public function has(string $name): bool;

    public function get(string $name): ModuleInterface;

    /**
     * @return list<string>
     */
    public function names(): array;
}
