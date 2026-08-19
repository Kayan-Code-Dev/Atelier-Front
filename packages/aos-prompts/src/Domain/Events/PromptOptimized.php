<?php

declare(strict_types=1);

namespace DressnMore\Aos\Prompts\Domain\Events;

final class PromptOptimized extends PromptDomainEvent
{
    public function __construct(
        string $correlationId,
        public readonly int $sectionCount,
        public readonly int $estimatedTokens,
    ) {
        parent::__construct($correlationId);
    }
}
