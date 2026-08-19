<?php

declare(strict_types=1);

namespace DressnMore\Aos\Tools\Domain\Exceptions;

final class ToolExecutionException extends ToolException
{
    public static function failed(string $reason): self
    {
        return new self('Tool execution failed: '.$reason);
    }
}
