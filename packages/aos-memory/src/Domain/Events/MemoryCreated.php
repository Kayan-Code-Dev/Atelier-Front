<?php

declare(strict_types=1);

namespace DressnMore\Aos\Memory\Domain\Events;

final class MemoryCreated extends MemoryDomainEvent
{
    public function __construct(
        string $correlationId,
        public readonly string $memoryId,
        public readonly string $type,
        public readonly string $tenantId,
    ) {
        parent::__construct($correlationId);
    }
}
