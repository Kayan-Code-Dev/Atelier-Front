<?php

declare(strict_types=1);

namespace DressnMore\Aos\Permissions\Domain\Permission;

final class PermissionRegistry implements PermissionRegistryInterface
{
    /** @var array<string, PermissionDefinition> */
    private array $items = [];

    public function register(PermissionDefinition $definition): void
    {
        $this->items[$definition->code()->toString()] = $definition;
    }

    public function get(PermissionCode $code): ?PermissionDefinition
    {
        return $this->items[$code->toString()] ?? null;
    }

    public function has(PermissionCode $code): bool
    {
        return isset($this->items[$code->toString()]);
    }

    public function all(): array
    {
        return array_values($this->items);
    }
}
