<?php

declare(strict_types=1);

namespace DressnMore\Aos\Knowledge\Domain\Events;

final class KnowledgeUpdated extends KnowledgeDomainEvent
{
    public function __construct(
        string $correlationId,
        public readonly string $knowledgeId,
        public readonly string $version,
    ) {
        parent::__construct($correlationId);
    }
}
