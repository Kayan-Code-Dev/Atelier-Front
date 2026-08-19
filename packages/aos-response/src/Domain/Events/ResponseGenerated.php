<?php

declare(strict_types=1);

namespace DressnMore\Aos\Response\Domain\Events;

final class ResponseGenerated extends ResponseDomainEvent
{
    public function __construct(
        string $correlationId,
        public readonly string $status,
        public readonly string $locale,
    ) {
        parent::__construct($correlationId);
    }
}
