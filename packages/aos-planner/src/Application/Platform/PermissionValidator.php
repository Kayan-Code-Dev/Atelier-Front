<?php

declare(strict_types=1);

namespace DressnMore\Aos\Planner\Application\Platform;

use DressnMore\Aos\Planner\Domain\Platform\PlatformPlanningContext;
use DressnMore\Aos\Planner\Domain\Platform\ToolSelection;
use RuntimeException;

/**
 * Enforces the current user's tool grants / permission list.
 */
final class PermissionValidator
{
    public function assert(PlatformPlanningContext $context, ToolSelection $selection): void
    {
        $granted = $context->grantedTools();
        $permissions = $context->permissions();

        if ($granted === [] && $permissions === []) {
            return;
        }

        foreach ($selection->selectedTools() as $tool) {
            $okGranted = $granted === []
                || in_array('*', $granted, true)
                || in_array($tool, $granted, true);
            $okPerm = $permissions === []
                || in_array('*', $permissions, true)
                || in_array($tool, $permissions, true);

            if (! $okGranted || ! $okPerm) {
                throw new RuntimeException('Permission denied for tool: '.$tool);
            }
        }
    }
}
