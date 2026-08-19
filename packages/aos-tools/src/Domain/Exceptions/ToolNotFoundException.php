<?php

declare(strict_types=1);

namespace DressnMore\Aos\Tools\Domain\Exceptions;

use DressnMore\Aos\Tools\Domain\Tool\ToolIdentifier;

final class ToolNotFoundException extends ToolException
{
    public static function for(ToolIdentifier $identifier): self
    {
        return new self(sprintf('Unknown tool [%s].', $identifier->toString()));
    }
}
