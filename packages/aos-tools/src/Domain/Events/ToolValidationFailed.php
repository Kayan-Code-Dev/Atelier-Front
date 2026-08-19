<?php

declare(strict_types=1);

namespace DressnMore\Aos\Tools\Domain\Events;

use DressnMore\Aos\Tools\Domain\Request\CorrelationId;
use DressnMore\Aos\Tools\Domain\Tool\ToolIdentifier;

final class ToolValidationFailed extends ToolDomainEvent
{
    /**
     * @param  list<string>  $messages
     */
    public function __construct(
        ToolIdentifier $toolIdentifier,
        public readonly CorrelationId $correlationId,
        public readonly array $messages,
    ) {
        parent::__construct($toolIdentifier);
    }
}
