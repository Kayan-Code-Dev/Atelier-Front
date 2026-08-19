<?php

declare(strict_types=1);

namespace DressnMore\Aos\Tools\Domain\Events;

use DressnMore\Aos\Tools\Domain\Tool\ToolIdentifier;
use DressnMore\Aos\Tools\Domain\Tool\ToolVersion;

final class ToolResolved extends ToolDomainEvent
{
    public function __construct(
        ToolIdentifier $toolIdentifier,
        public readonly ToolVersion $version,
    ) {
        parent::__construct($toolIdentifier);
    }
}
