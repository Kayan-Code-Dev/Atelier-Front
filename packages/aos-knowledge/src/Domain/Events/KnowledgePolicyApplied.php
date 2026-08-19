<?php

declare(strict_types=1);

namespace DressnMore\Aos\Knowledge\Domain\Events;

final class KnowledgePolicyApplied extends KnowledgeDomainEvent
{
    /**
     * @param  list<string>  $notes
     */
    public function __construct(
        string $correlationId,
        public readonly array $notes,
    ) {
        parent::__construct($correlationId);
    }
}
