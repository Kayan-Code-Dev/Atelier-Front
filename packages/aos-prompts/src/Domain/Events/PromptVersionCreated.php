<?php

declare(strict_types=1);

namespace DressnMore\Aos\Prompts\Domain\Events;

final class PromptVersionCreated extends PromptDomainEvent
{
    public function __construct(
        string $correlationId,
        public readonly string $promptId,
        public readonly string $version,
        public readonly string $templateVersion,
    ) {
        parent::__construct($correlationId);
    }
}
