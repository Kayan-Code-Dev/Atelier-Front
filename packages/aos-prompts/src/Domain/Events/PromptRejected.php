<?php

declare(strict_types=1);

namespace DressnMore\Aos\Prompts\Domain\Events;

final class PromptRejected extends PromptDomainEvent
{
    public function __construct(
        string $correlationId,
        public readonly string $reason,
    ) {
        parent::__construct($correlationId);
    }
}
