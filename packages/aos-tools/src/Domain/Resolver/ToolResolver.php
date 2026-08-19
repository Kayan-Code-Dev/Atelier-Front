<?php

declare(strict_types=1);

namespace DressnMore\Aos\Tools\Domain\Resolver;

use DressnMore\Aos\Tools\Domain\Contracts\BusinessToolHandlerInterface;
use DressnMore\Aos\Tools\Domain\Exceptions\ToolNotFoundException;
use DressnMore\Aos\Tools\Domain\Registry\ToolRegistry;
use DressnMore\Aos\Tools\Domain\Tool\ToolIdentifier;
use DressnMore\Aos\Tools\Domain\Tool\ToolManifest;
use DressnMore\Aos\Tools\Domain\Tool\ToolMetadata;

final class ToolResolver
{
    public function __construct(
        private readonly ToolRegistry $registry,
    ) {}

    public function resolve(ToolIdentifier $identifier): ResolvedTool
    {
        $handler = $this->registry->require($identifier);
        $manifest = $handler->manifest();

        return new ResolvedTool($handler, $manifest, $manifest->toMetadata());
    }

    public function tryResolve(ToolIdentifier $identifier): ?ResolvedTool
    {
        try {
            return $this->resolve($identifier);
        } catch (ToolNotFoundException) {
            return null;
        }
    }
}
