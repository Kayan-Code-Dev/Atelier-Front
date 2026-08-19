<?php

declare(strict_types=1);

namespace DressnMore\Aos\ToolRegistry\Contracts;

use DressnMore\Aos\ToolRegistry\Domain\Tool\ToolDescriptor;
use DressnMore\Aos\ToolRegistry\Domain\Tool\ToolMetadata;

interface ToolMetadataRegistryInterface
{
    public function put(ToolMetadata $metadata): void;

    public function get(string $toolName): ?ToolMetadata;

    /**
     * @return list<ToolMetadata>
     */
    public function all(): array;

    public function syncFromDescriptor(ToolDescriptor $descriptor): void;
}
