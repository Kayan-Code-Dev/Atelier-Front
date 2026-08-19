<?php

declare(strict_types=1);

namespace DressnMore\Aos\Tools\Domain\Specifications;

use DressnMore\Aos\Tools\Domain\Result\ExecutionStatus;
use DressnMore\Aos\Tools\Domain\Result\ToolResult;

final class ToolResultIsSuccessful
{
    public function isSatisfiedBy(ToolResult $result): bool
    {
        return $result->status() === ExecutionStatus::Success;
    }
}
