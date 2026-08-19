<?php

declare(strict_types=1);

namespace DressnMore\Aos\Tools\Domain\Contracts;

use DressnMore\Aos\Tools\Domain\Request\ToolRequest;
use DressnMore\Aos\Tools\Domain\Result\ToolResult;
use DressnMore\Aos\Tools\Domain\Tool\ToolManifest;

interface ToolAnalyticsHookInterface
{
    public function record(ToolRequest $request, ToolManifest $manifest, ToolResult $result): string;
}
