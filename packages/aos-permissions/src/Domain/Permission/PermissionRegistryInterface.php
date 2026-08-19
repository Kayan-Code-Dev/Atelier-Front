<?php

declare(strict_types=1);

namespace DressnMore\Aos\Permissions\Domain\Permission;

interface PermissionRegistryInterface
{
    public function register(PermissionDefinition $definition): void;

    public function get(PermissionCode $code): ?PermissionDefinition;

    public function has(PermissionCode $code): bool;

    /**
     * @return list<PermissionDefinition>
     */
    public function all(): array;
}
