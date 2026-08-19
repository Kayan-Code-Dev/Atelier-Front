<?php

declare(strict_types=1);

namespace DressnMore\Aos\Memory\Domain\Events;

final class MemoryUpdated extends MemoryDomainEvent
{
    public function __construct(
        string $correlationId,
        public readonly string $memoryId,
        public readonly string $tenantId,
    ) {
        parent::__construct($correlationId);
    }
}
