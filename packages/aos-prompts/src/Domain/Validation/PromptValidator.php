<?php

declare(strict_types=1);

namespace DressnMore\Aos\Prompts\Domain\Validation;

use DressnMore\Aos\Prompts\Domain\Persona\Persona;
use DressnMore\Aos\Prompts\Domain\Prompt\TokenBudget;
use DressnMore\Aos\Prompts\Domain\Section\PromptSection;
use DressnMore\Aos\Prompts\Domain\Section\PromptSectionType;

final class PromptValidator
{
    /**
     * @param  list<PromptSection>  $sections
     */
    public function validate(array $sections, ?Persona $persona, TokenBudget $budget): ValidationResult
    {
        $errors = [];
        $warnings = [];

        if ($persona === null) {
            $errors[] = 'missing_persona';
        }

        $byType = [];
        foreach ($sections as $section) {
            $byType[$section->type()->value] = $section;
        }

        foreach ([PromptSectionType::System, PromptSectionType::Persona, PromptSectionType::SafetyInstructions, PromptSectionType::CurrentUserMessage] as $required) {
            $section = $byType[$required->value] ?? null;
            if ($section === null || $section->isEmpty()) {
                $errors[] = 'missing_section:'.$required->value;
            }
        }

        if (! isset($byType[PromptSectionType::ResponseConstraints->value])) {
            $warnings[] = 'missing_constraints';
        }

        if ($budget->exceeds()) {
            $errors[] = 'token_budget_exceeded';
        } elseif ($budget->estimatedTokens() > (int) ($budget->maxTokens() * 0.85)) {
            $warnings[] = 'token_budget_near_limit';
        }

        return new ValidationResult($errors === [], $errors, $warnings);
    }
}
