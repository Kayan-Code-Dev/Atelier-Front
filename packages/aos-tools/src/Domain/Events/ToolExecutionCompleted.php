<?php

declare(strict_types=1);

namespace DressnMore\Aos\Tools\Domain\Events;

use DressnMore\Aos\Tools\Domain\Request\CorrelationId;
use DressnMore\Aos\Tools\Domain\Result\ExecutionStatus;
use DressnMore\Aos\Tools\Domain\Tool\ToolIdentifier;

final class ToolExecutionCompleted extends ToolDomainEvent
{
    public function __construct(
        ToolIdentifier $toolIdentifier,
        public readonly CorrelationId $correlationId,
        public readonly ExecutionStatus $status,
        public readonly float $executionTimeMs,
    ) {
        parent::__construct($toolIdentifier);
    }
}
