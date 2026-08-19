<?php

declare(strict_types=1);

namespace DressnMore\Aos\Knowledge\Domain\Events;

final class KnowledgeCreated extends KnowledgeDomainEvent
{
    public function __construct(
        string $correlationId,
        public readonly string $knowledgeId,
        public readonly string $type,
        public readonly ?string $tenantId,
    ) {
        parent::__construct($correlationId);
    }
}
