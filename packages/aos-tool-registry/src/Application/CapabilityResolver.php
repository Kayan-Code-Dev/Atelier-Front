<?php

declare(strict_types=1);

namespace DressnMore\Aos\ToolRegistry\Application;

use DressnMore\Aos\ToolRegistry\Contracts\CapabilityRegistryInterface;
use DressnMore\Aos\ToolRegistry\Domain\Capability\CapabilityDescriptor;
use RuntimeException;

final class CapabilityResolver
{
    public function __construct(private readonly CapabilityRegistryInterface $registry) {}

    public function resolve(string $capability): CapabilityDescriptor
    {
        $found = $this->registry->get($capability);
        if ($found === null) {
            throw new RuntimeException('Capability not registered: '.$capability);
        }

        return $found;
    }
}
