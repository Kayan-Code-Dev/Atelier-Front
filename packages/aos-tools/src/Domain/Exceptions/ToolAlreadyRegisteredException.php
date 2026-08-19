<?php

declare(strict_types=1);

namespace DressnMore\Aos\Tools\Domain\Exceptions;

use DressnMore\Aos\Tools\Domain\Tool\ToolIdentifier;

final class ToolAlreadyRegisteredException extends ToolException
{
    public static function for(ToolIdentifier $identifier): self
    {
        return new self(sprintf('Tool [%s] is already registered.', $identifier->toString()));
    }
}
