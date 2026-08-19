<?php

declare(strict_types=1);

namespace DressnMore\Aos\Prompts\Domain\Events;

final class PromptValidated extends PromptDomainEvent
{
    /**
     * @param  list<string>  $errors
     * @param  list<string>  $warnings
     */
    public function __construct(
        string $correlationId,
        public readonly bool $valid,
        public readonly array $errors,
        public readonly array $warnings,
    ) {
        parent::__construct($correlationId);
    }
}
