<?php

declare(strict_types=1);

namespace DressnMore\Aos\ToolRegistry\Application;

use DressnMore\Aos\ToolRegistry\Contracts\ToolRegistryInterface;
use DressnMore\Aos\ToolRegistry\Domain\Tool\ToolDescriptor;
use InvalidArgumentException;

final class ToolRegistry implements ToolRegistryInterface
{
    /** @var array<string, ToolDescriptor> */
    private array $tools = [];

    public function register(ToolDescriptor $descriptor): void
    {
        $name = $descriptor->name();
        if ($name === '') {
            throw new InvalidArgumentException('Tool name cannot be empty.');
        }
        $this->tools[$name] = $descriptor;
    }

    public function has(string $toolName): bool
    {
        return isset($this->tools[$toolName]);
    }

    public function get(string $toolName): ?ToolDescriptor
    {
        return $this->tools[$toolName] ?? null;
    }

    public function all(): array
    {
        return array_values($this->tools);
    }

    public function byCategory(string $category): array
    {
        return array_values(array_filter(
            $this->tools,
            static fn (ToolDescriptor $d): bool => $d->category()->value === $category,
        ));
    }

    public function byOwnerDomain(string $ownerDomain): array
    {
        return array_values(array_filter(
            $this->tools,
            static fn (ToolDescriptor $d): bool => $d->metadata()->ownerDomain() === $ownerDomain,
        ));
    }
}
