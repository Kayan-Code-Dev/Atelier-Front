<?php

declare(strict_types=1);

namespace DressnMore\Aos\Response\Domain\Events;

final class ResponseFailed extends ResponseDomainEvent
{
    public function __construct(
        string $correlationId,
        public readonly string $reasonCode,
        public readonly string $message,
    ) {
        parent::__construct($correlationId);
    }
}
