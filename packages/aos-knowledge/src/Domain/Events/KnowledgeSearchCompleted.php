<?php

declare(strict_types=1);

namespace DressnMore\Aos\Knowledge\Domain\Events;

final class KnowledgeSearchCompleted extends KnowledgeDomainEvent
{
    public function __construct(
        string $correlationId,
        public readonly string $query,
        public readonly int $hitCount,
    ) {
        parent::__construct($correlationId);
    }
}
