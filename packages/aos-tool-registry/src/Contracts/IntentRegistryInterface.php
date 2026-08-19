<?php

declare(strict_types=1);

namespace DressnMore\Aos\ToolRegistry\Contracts;

use DressnMore\Aos\ToolRegistry\Domain\Intent\IntentDescriptor;

interface IntentRegistryInterface
{
    public function register(IntentDescriptor $descriptor): void;

    public function has(string $intent): bool;

    public function get(string $intent): ?IntentDescriptor;

    /**
     * @return list<IntentDescriptor>
     */
    public function all(): array;
}
