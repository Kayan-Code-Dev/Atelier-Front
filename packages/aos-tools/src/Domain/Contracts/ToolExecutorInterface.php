<?php

declare(strict_types=1);

namespace DressnMore\Aos\Tools\Domain\Contracts;

use DressnMore\Aos\Tools\Domain\Request\ToolRequest;
use DressnMore\Aos\Tools\Domain\Result\ToolResult;
use DressnMore\Aos\Tools\Domain\Tool\ToolManifest;
use DressnMore\Aos\Tools\Domain\Tool\ToolMetadata;

interface ToolExecutorInterface
{
    public function execute(ToolRequest $request, ToolManifest $manifest, ToolMetadata $metadata): ToolResult;
}
