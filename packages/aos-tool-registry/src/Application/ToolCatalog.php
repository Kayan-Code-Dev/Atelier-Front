<?php

declare(strict_types=1);

namespace DressnMore\Aos\ToolRegistry\Application;

use DressnMore\Aos\ToolRegistry\Contracts\ToolRegistryInterface;
use DressnMore\Aos\ToolRegistry\Domain\Tool\ToolDescriptor;

/**
 * Read-only catalog projection over the Tool Registry.
 */
final class ToolCatalog
{
    public function __construct(private readonly ToolRegistryInterface $registry) {}

    /**
     * @return list<ToolDescriptor>
     */
    public function entries(): array
    {
        return $this->registry->all();
    }

    public function count(): int
    {
        return count($this->registry->all());
    }

    /**
     * @return list<string>
     */
    public function names(): array
    {
        return array_map(static fn (ToolDescriptor $d): string => $d->name(), $this->registry->all());
    }
}
