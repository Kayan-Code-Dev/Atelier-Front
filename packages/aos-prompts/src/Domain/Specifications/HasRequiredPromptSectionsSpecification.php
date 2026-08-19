<?php

declare(strict_types=1);

namespace DressnMore\Aos\Prompts\Domain\Specifications;

use DressnMore\Aos\Prompts\Domain\Section\PromptSection;
use DressnMore\Aos\Prompts\Domain\Section\PromptSectionType;

final class HasRequiredPromptSectionsSpecification
{
    /**
     * @param  list<PromptSection>  $sections
     */
    public function isSatisfiedBy(array $sections): bool
    {
        $present = [];
        foreach ($sections as $section) {
            if (! $section->isEmpty()) {
                $present[$section->type()->value] = true;
            }
        }

        foreach ([
            PromptSectionType::System,
            PromptSectionType::Persona,
            PromptSectionType::SafetyInstructions,
            PromptSectionType::CurrentUserMessage,
        ] as $required) {
            if (! isset($present[$required->value])) {
                return false;
            }
        }

        return true;
    }
}
