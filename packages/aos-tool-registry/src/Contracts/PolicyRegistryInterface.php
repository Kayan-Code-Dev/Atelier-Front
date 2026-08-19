<?php

declare(strict_types=1);

namespace DressnMore\Aos\ToolRegistry\Contracts;

use DressnMore\Aos\ToolRegistry\Domain\Policy\PolicyDescriptor;

interface PolicyRegistryInterface
{
    public function register(PolicyDescriptor $descriptor): void;

    public function get(string $name): ?PolicyDescriptor;

    /**
     * @return list<PolicyDescriptor>
     */
    public function all(): array;
}
