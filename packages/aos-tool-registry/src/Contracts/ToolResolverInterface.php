<?php

declare(strict_types=1);

namespace DressnMore\Aos\ToolRegistry\Contracts;

use DressnMore\Aos\ToolRegistry\Domain\Tool\ToolDescriptor;
use DressnMore\Aos\ToolRegistry\Domain\Tool\ToolVersion;

interface ToolResolverInterface
{
    public function resolve(string $toolName, ?ToolVersion $minimumVersion = null): ToolDescriptor;
}
