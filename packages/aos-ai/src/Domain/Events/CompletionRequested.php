<?php

declare(strict_types=1);

namespace DressnMore\Aos\Ai\Domain\Events;

final class CompletionRequested extends AiDomainEvent
{
    public function __construct(
        string $correlationId,
        public readonly bool $streaming,
    ) {
        parent::__construct($correlationId);
    }
}
