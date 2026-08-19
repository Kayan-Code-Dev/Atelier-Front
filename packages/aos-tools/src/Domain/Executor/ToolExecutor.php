<?php

declare(strict_types=1);

namespace DressnMore\Aos\Tools\Domain\Executor;

use DressnMore\Aos\Tools\Domain\Contracts\ToolExecutorInterface;
use DressnMore\Aos\Tools\Domain\Registry\ToolRegistry;
use DressnMore\Aos\Tools\Domain\Request\ToolRequest;
use DressnMore\Aos\Tools\Domain\Result\ToolResult;
use DressnMore\Aos\Tools\Domain\Tool\ToolManifest;
use DressnMore\Aos\Tools\Domain\Tool\ToolMetadata;

/**
 * Dispatches to the registered handler by ToolIdentifier.
 */
final class ToolExecutor implements ToolExecutorInterface
{
    public function __construct(
        private readonly ToolRegistry $registry,
    ) {}

    public function execute(ToolRequest $request, ToolManifest $manifest, ToolMetadata $metadata): ToolResult
    {
        $started = hrtime(true);
        $handler = $this->registry->require($manifest->identifier());
        $result = $handler->execute($request);
        $elapsedMs = (hrtime(true) - $started) / 1_000_000;

        return $result->withExecutionTime($elapsedMs);
    }
}
