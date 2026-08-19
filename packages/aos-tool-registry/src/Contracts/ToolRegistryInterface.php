<?php

declare(strict_types=1);

namespace DressnMore\Aos\ToolRegistry\Contracts;

use DressnMore\Aos\ToolRegistry\Domain\Tool\ToolDescriptor;

interface ToolRegistryInterface
{
    public function register(ToolDescriptor $descriptor): void;

    public function has(string $toolName): bool;

    public function get(string $toolName): ?ToolDescriptor;

    /**
     * @return list<ToolDescriptor>
     */
    public function all(): array;

    /**
     * @return list<ToolDescriptor>
     */
    public function byCategory(string $category): array;

    /**
     * @return list<ToolDescriptor>
     */
    public function byOwnerDomain(string $ownerDomain): array;
}
