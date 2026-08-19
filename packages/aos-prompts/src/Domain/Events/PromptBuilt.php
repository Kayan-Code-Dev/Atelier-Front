<?php

declare(strict_types=1);

namespace DressnMore\Aos\Prompts\Domain\Events;

final class PromptBuilt extends PromptDomainEvent
{
    public function __construct(
        string $correlationId,
        public readonly string $promptId,
        public readonly int $sectionCount,
    ) {
        parent::__construct($correlationId);
    }
}
