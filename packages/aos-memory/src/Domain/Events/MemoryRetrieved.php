<?php

declare(strict_types=1);

namespace DressnMore\Aos\Memory\Domain\Events;

final class MemoryRetrieved extends MemoryDomainEvent
{
    public function __construct(
        string $correlationId,
        public readonly string $tenantId,
        public readonly int $count,
    ) {
        parent::__construct($correlationId);
    }
}
