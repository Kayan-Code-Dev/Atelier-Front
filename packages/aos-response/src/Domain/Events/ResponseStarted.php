<?php

declare(strict_types=1);

namespace DressnMore\Aos\Response\Domain\Events;

final class ResponseStarted extends ResponseDomainEvent
{
    public function __construct(
        string $correlationId,
        public readonly ?string $planId,
        public readonly int $outcomeCount,
    ) {
        parent::__construct($correlationId);
    }
}
