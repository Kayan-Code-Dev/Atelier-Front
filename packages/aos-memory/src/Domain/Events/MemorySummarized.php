<?php

declare(strict_types=1);

namespace DressnMore\Aos\Memory\Domain\Events;

final class MemorySummarized extends MemoryDomainEvent
{
    public function __construct(
        string $correlationId,
        public readonly string $conversationId,
        public readonly string $kind,
    ) {
        parent::__construct($correlationId);
    }
}
