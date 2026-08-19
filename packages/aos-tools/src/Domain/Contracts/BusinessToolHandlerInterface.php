<?php

declare(strict_types=1);

namespace DressnMore\Aos\Tools\Domain\Contracts;

use DressnMore\Aos\Tools\Domain\Request\ToolRequest;
use DressnMore\Aos\Tools\Domain\Result\ToolResult;
use DressnMore\Aos\Tools\Domain\Tool\ToolIdentifier;
use DressnMore\Aos\Tools\Domain\Tool\ToolManifest;

/**
 * Business tool handler contract — keyed by ToolIdentifier, not class name in the registry.
 */
interface BusinessToolHandlerInterface
{
    public function identifier(): ToolIdentifier;

    public function manifest(): ToolManifest;

    public function execute(ToolRequest $request): ToolResult;
}
