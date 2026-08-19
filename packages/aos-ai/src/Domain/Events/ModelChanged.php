<?php

declare(strict_types=1);

namespace DressnMore\Aos\Ai\Domain\Events;

final class ModelChanged extends AiDomainEvent
{
    public function __construct(
        string $correlationId,
        public readonly string $fromModelId,
        public readonly string $toModelId,
    ) {
        parent::__construct($correlationId);
    }
}
