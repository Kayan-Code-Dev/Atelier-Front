<?php

declare(strict_types=1);

namespace DressnMore\Aos\Memory\Domain\Events;

final class MemoryDiscarded extends MemoryDomainEvent
{
    public function __construct(
        string $correlationId,
        public readonly string $memoryId,
        public readonly string $reason,
    ) {
        parent::__construct($correlationId);
    }
}
