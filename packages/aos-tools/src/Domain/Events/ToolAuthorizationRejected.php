<?php

declare(strict_types=1);

namespace DressnMore\Aos\Tools\Domain\Events;

use DressnMore\Aos\Tools\Domain\Request\CorrelationId;
use DressnMore\Aos\Tools\Domain\Tool\ToolIdentifier;

final class ToolAuthorizationRejected extends ToolDomainEvent
{
    public function __construct(
        ToolIdentifier $toolIdentifier,
        public readonly CorrelationId $correlationId,
        public readonly string $reason,
    ) {
        parent::__construct($toolIdentifier);
    }
}
