<?php

declare(strict_types=1);

namespace DressnMore\Aos\ToolRegistry\Application;

use DressnMore\Aos\ToolRegistry\Domain\Tool\ToolDescriptor;

/**
 * Loads conceptual tool descriptors from provider packs (no execution wiring).
 */
final class ToolLoader
{
    /**
     * @param list<ToolDescriptor> $descriptors
     * @return list<ToolDescriptor>
     */
    public function load(array $descriptors): array
    {
        $loaded = [];
        foreach ($descriptors as $descriptor) {
            if (! $descriptor instanceof ToolDescriptor) {
                continue;
            }
            $loaded[] = $descriptor;
        }

        return $loaded;
    }
}
