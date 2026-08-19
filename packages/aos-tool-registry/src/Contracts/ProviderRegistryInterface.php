<?php

declare(strict_types=1);

namespace DressnMore\Aos\ToolRegistry\Contracts;

use DressnMore\Aos\ToolRegistry\Domain\Provider\ProviderDescriptor;

interface ProviderRegistryInterface
{
    public function register(ProviderDescriptor $descriptor): void;

    public function get(string $id): ?ProviderDescriptor;

    /**
     * @return list<ProviderDescriptor>
     */
    public function all(): array;
}
