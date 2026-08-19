<?php

declare(strict_types=1);

namespace DressnMore\Aos\Prompts\Domain\Validation;

use DressnMore\Aos\Prompts\Domain\Persona\Persona;
use DressnMore\Aos\Prompts\Domain\Prompt\TokenBudget;
use DressnMore\Aos\Prompts\Domain\Section\PromptSection;
use DressnMore\Aos\Prompts\Domain\Section\PromptSectionType;

final class ValidationResult
{
    /**
     * @param  list<string>  $errors
     * @param  list<string>  $warnings
     */
    public function __construct(
        private readonly bool $valid,
        private readonly array $errors = [],
        private readonly array $warnings = [],
    ) {}

    public function isValid(): bool
    {
        return $this->valid;
    }

    /**
     * @return list<string>
     */
    public function errors(): array
    {
        return $this->errors;
    }

    /**
     * @return list<string>
     */
    public function warnings(): array
    {
        return $this->warnings;
    }
}
