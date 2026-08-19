<?php

declare(strict_types=1);

namespace DressnMore\Aos\Knowledge\Domain\Events;

final class KnowledgePublished extends KnowledgeDomainEvent
{
    public function __construct(
        string $correlationId,
        public readonly string $knowledgeId,
    ) {
        parent::__construct($correlationId);
    }
}
