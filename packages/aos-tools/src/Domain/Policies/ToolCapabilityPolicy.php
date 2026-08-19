<?php

declare(strict_types=1);

namespace DressnMore\Aos\Tools\Domain\Policies;

use DressnMore\Aos\Tools\Domain\Context\ToolExecutionContext;
use DressnMore\Aos\Tools\Domain\Tool\ToolManifest;

/**
 * Capability / permission intersection checks (Permission Engine remains external).
 */
final class ToolCapabilityPolicy
{
    public function hasRequiredCapabilities(ToolManifest $manifest, ToolExecutionContext $context): bool
    {
        foreach ($manifest->capabilities() as $capability) {
            if (! $context->hasCapability($capability)) {
                return false;
            }
        }

        return true;
    }

    public function hasRequiredPermissions(ToolManifest $manifest, ToolExecutionContext $context): bool
    {
        foreach ($manifest->permissions() as $permission) {
            if (! $context->allowsPermission($permission)) {
                return false;
            }
        }

        return true;
    }
}
