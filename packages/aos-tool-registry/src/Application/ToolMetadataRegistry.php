<?php

declare(strict_types=1);

namespace DressnMore\Aos\ToolRegistry\Application;

use DressnMore\Aos\ToolRegistry\Contracts\ToolMetadataRegistryInterface;
use DressnMore\Aos\ToolRegistry\Domain\Tool\ToolDescriptor;
use DressnMore\Aos\ToolRegistry\Domain\Tool\ToolMetadata;

final class ToolMetadataRegistry implements ToolMetadataRegistryInterface
{
    /** @var array<string, ToolMetadata> */
    private array $items = [];

    public function put(ToolMetadata $metadata): void
    {
        $this->items[$metadata->toolName()] = $metadata;
    }

    public function get(string $toolName): ?ToolMetadata
    {
        return $this->items[$toolName] ?? null;
    }

    public function all(): array
    {
        return array_values($this->items);
    }

    public function syncFromDescriptor(ToolDescriptor $descriptor): void
    {
        $this->put($descriptor->metadata());
    }
}
