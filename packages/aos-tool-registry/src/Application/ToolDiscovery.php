<?php

declare(strict_types=1);

namespace DressnMore\Aos\ToolRegistry\Application;

use DressnMore\Aos\ToolRegistry\Contracts\ToolDiscoveryInterface;
use DressnMore\Aos\ToolRegistry\Contracts\ToolRegistryInterface;
use DressnMore\Aos\ToolRegistry\Domain\Tool\ToolDescriptor;

final class ToolDiscovery implements ToolDiscoveryInterface
{
    public function __construct(private readonly ToolRegistryInterface $registry) {}

    public function discover(?string $category = null, ?string $ownerDomain = null): array
    {
        $tools = $this->registry->all();
        $tools = array_values(array_filter(
            $tools,
            static fn (ToolDescriptor $d): bool => $d->isDiscoverable(),
        ));

        if ($category !== null) {
            $tools = array_values(array_filter(
                $tools,
                static fn (ToolDescriptor $d): bool => $d->category()->value === $category,
            ));
        }

        if ($ownerDomain !== null) {
            $tools = array_values(array_filter(
                $tools,
                static fn (ToolDescriptor $d): bool => $d->metadata()->ownerDomain() === $ownerDomain,
            ));
        }

        return $tools;
    }

    public function find(string $toolName): ?ToolDescriptor
    {
        $tool = $this->registry->get($toolName);
        if ($tool === null || ! $tool->isDiscoverable()) {
            return null;
        }

        return $tool;
    }

    public function byCapability(string $capability): array
    {
        return array_values(array_filter(
            $this->discover(),
            static fn (ToolDescriptor $d): bool => in_array($capability, $d->capabilities(), true),
        ));
    }
}
