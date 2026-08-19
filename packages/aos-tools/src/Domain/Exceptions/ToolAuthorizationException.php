<?php

declare(strict_types=1);

namespace DressnMore\Aos\Tools\Domain\Exceptions;

use DressnMore\Aos\Tools\Domain\Tool\ToolIdentifier;

final class ToolAuthorizationException extends ToolException
{
    public static function rejected(ToolIdentifier $identifier, string $reason = 'denied'): self
    {
        return new self(sprintf('Tool [%s] authorization rejected: %s', $identifier->toString(), $reason));
    }
}
