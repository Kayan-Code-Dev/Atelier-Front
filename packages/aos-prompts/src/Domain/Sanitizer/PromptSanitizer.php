<?php

declare(strict_types=1);

namespace DressnMore\Aos\Prompts\Domain\Sanitizer;

use DressnMore\Aos\Prompts\Domain\Section\PromptSection;

/**
 * Sanitizes section content (trim, collapse blank lines, strip null bytes).
 */
final class PromptSanitizer
{
    public function sanitizeText(string $text): string
    {
        $text = str_replace("\0", '', $text);
        $text = trim($text);
        $text = (string) preg_replace("/\n{3,}/", "\n\n", $text);

        return $text;
    }

    public function sanitizeSection(PromptSection $section): PromptSection
    {
        return $section->withContent($this->sanitizeText($section->content()));
    }

    /**
     * @param  list<PromptSection>  $sections
     * @return list<PromptSection>
     */
    public function sanitizeSections(array $sections): array
    {
        return array_map(fn (PromptSection $s): PromptSection => $this->sanitizeSection($s), $sections);
    }
}
