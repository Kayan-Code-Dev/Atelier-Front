<?php

declare(strict_types=1);

namespace DressnMore\Aos\ToolRegistry\Application;

use DressnMore\Aos\ToolRegistry\Contracts\IntentRegistryInterface;
use DressnMore\Aos\ToolRegistry\Domain\Intent\IntentDescriptor;
use RuntimeException;

final class IntentResolver
{
    public function __construct(private readonly IntentRegistryInterface $registry) {}

    public function resolve(string $intent): IntentDescriptor
    {
        $found = $this->registry->get($intent);
        if ($found === null) {
            throw new RuntimeException('Intent not registered: '.$intent);
        }

        return $found;
    }
}
