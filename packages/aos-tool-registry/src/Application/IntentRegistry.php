<?php

declare(strict_types=1);

namespace DressnMore\Aos\ToolRegistry\Application;

use DressnMore\Aos\ToolRegistry\Contracts\IntentRegistryInterface;
use DressnMore\Aos\ToolRegistry\Domain\Intent\IntentDescriptor;
use InvalidArgumentException;

final class IntentRegistry implements IntentRegistryInterface
{
    /** @var array<string, IntentDescriptor> */
    private array $intents = [];

    public function register(IntentDescriptor $descriptor): void
    {
        if ($descriptor->intent() === '') {
            throw new InvalidArgumentException('Intent name cannot be empty.');
        }
        $this->intents[$descriptor->intent()] = $descriptor;
    }

    public function has(string $intent): bool
    {
        return isset($this->intents[$intent]);
    }

    public function get(string $intent): ?IntentDescriptor
    {
        return $this->intents[$intent] ?? null;
    }

    public function all(): array
    {
        return array_values($this->intents);
    }
}
