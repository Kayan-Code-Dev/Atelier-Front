<?php

declare(strict_types=1);

namespace DressnMore\Aos\ToolRegistry\Application;

use DressnMore\Aos\ToolRegistry\Contracts\PolicyRegistryInterface;
use DressnMore\Aos\ToolRegistry\Domain\Policy\PolicyDescriptor;

final class PolicyRegistry implements PolicyRegistryInterface
{
    /** @var array<string, PolicyDescriptor> */
    private array $policies = [];

    public function register(PolicyDescriptor $descriptor): void
    {
        $this->policies[$descriptor->name()] = $descriptor;
    }

    public function get(string $name): ?PolicyDescriptor
    {
        return $this->policies[$name] ?? null;
    }

    public function all(): array
    {
        return array_values($this->policies);
    }
}
