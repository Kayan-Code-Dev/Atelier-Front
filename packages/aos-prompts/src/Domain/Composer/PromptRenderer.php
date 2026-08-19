<?php

declare(strict_types=1);

namespace DressnMore\Aos\Prompts\Domain\Composer;

use DressnMore\Aos\Prompts\Domain\Section\PromptSection;
use DressnMore\Aos\Prompts\Domain\Section\PromptSectionType;

/**
 * Renders ordered sections into a single provider-agnostic prompt string.
 */
final class PromptRenderer
{
    /**
     * @param  list<PromptSection>  $sections
     */
    public function render(array $sections): string
    {
        $blocks = [];
        foreach ($sections as $section) {
            if ($section->isEmpty()) {
                continue;
            }
            $blocks[] = '## '.$this->heading($section->type())."\n".$section->content();
        }

        return implode("\n\n", $blocks);
    }

    private function heading(PromptSectionType $type): string
    {
        return strtoupper(str_replace('_', ' ', $type->value));
    }
}
