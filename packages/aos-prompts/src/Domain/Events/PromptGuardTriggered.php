<?php

declare(strict_types=1);

namespace DressnMore\Aos\Prompts\Domain\Events;

final class PromptGuardTriggered extends PromptDomainEvent
{
    /**
     * @param  list<string>  $triggers
     */
    public function __construct(
        string $correlationId,
        public readonly string $verdict,
        public readonly array $triggers,
    ) {
        parent::__construct($correlationId);
    }
}
