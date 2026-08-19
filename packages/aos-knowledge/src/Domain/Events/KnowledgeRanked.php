<?php

declare(strict_types=1);

namespace DressnMore\Aos\Knowledge\Domain\Events;

final class KnowledgeRanked extends KnowledgeDomainEvent
{
    public function __construct(
        string $correlationId,
        public readonly int $count,
    ) {
        parent::__construct($correlationId);
    }
}
