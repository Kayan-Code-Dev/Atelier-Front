<?php

declare(strict_types=1);

namespace DressnMore\Aos\ToolRegistry\Contracts;

use DressnMore\Aos\ToolRegistry\Domain\Tool\ToolDescriptor;

interface ToolDiscoveryInterface
{
    /**
     * @return list<ToolDescriptor>
     */
    public function discover(?string $category = null, ?string $ownerDomain = null): array;

    public function find(string $toolName): ?ToolDescriptor;

    /**
     * @return list<ToolDescriptor>
     */
    public function byCapability(string $capability): array;
}
