<?php

declare(strict_types=1);

namespace DressnMore\Aos\Memory\Domain\Events;

final class MemoryRanked extends MemoryDomainEvent
{
    public function __construct(
        string $correlationId,
        public readonly int $count,
    ) {
        parent::__construct($correlationId);
    }
}
