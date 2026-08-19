<?php

declare(strict_types=1);

namespace DressnMore\Aos\Memory\Domain\Events;

final class SnapshotGenerated extends MemoryDomainEvent
{
    public function __construct(
        string $correlationId,
        public readonly string $kind,
        public readonly string $tenantId,
        public readonly int $count,
    ) {
        parent::__construct($correlationId);
    }
}
