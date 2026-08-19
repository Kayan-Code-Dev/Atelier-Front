<?php

declare(strict_types=1);

namespace DressnMore\Aos\Knowledge\Domain\Events;

final class KnowledgeRejected extends KnowledgeDomainEvent
{
    public function __construct(
        string $correlationId,
        public readonly string $knowledgeId,
        public readonly string $reason,
    ) {
        parent::__construct($correlationId);
    }
}
