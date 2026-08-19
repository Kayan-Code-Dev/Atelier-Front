<?php

declare(strict_types=1);

namespace DressnMore\Aos\Tools\Domain\Policies;

use DressnMore\Aos\Tools\Domain\Tool\ToolManifest;
use DressnMore\Aos\Tools\Domain\Tool\ToolOperatingMode;

final class ToolModePolicy
{
    public function isAllowed(ToolManifest $manifest, ToolOperatingMode $mode): bool
    {
        return $manifest->supportsMode($mode);
    }
}
