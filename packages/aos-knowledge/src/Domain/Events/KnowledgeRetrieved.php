<?php

declare(strict_types=1);

namespace DressnMore\Aos\Knowledge\Domain\Events;

final class KnowledgeRetrieved extends KnowledgeDomainEvent
{
    public function __construct(
        string $correlationId,
        public readonly ?string $tenantId,
        public readonly int $count,
    ) {
        parent::__construct($correlationId);
    }
}
