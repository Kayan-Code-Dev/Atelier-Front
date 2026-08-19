<?php

declare(strict_types=1);

namespace DressnMore\Aos\Tools\Domain\Resolver;

use DressnMore\Aos\Tools\Domain\Contracts\BusinessToolHandlerInterface;
use DressnMore\Aos\Tools\Domain\Tool\ToolManifest;
use DressnMore\Aos\Tools\Domain\Tool\ToolMetadata;

final class ResolvedTool
{
    public function __construct(
        private readonly BusinessToolHandlerInterface $handler,
        private readonly ToolManifest $manifest,
        private readonly ToolMetadata $metadata,
    ) {}

    public function handler(): BusinessToolHandlerInterface
    {
        return $this->handler;
    }

    public function manifest(): ToolManifest
    {
        return $this->manifest;
    }

    public function metadata(): ToolMetadata
    {
        return $this->metadata;
    }
}
