<?php

declare(strict_types=1);

namespace DressnMore\Aos\Prompts\Domain\Optimizer;

use DressnMore\Aos\Prompts\Domain\Sanitizer\PromptSanitizer;
use DressnMore\Aos\Prompts\Domain\Section\PromptSection;
use DressnMore\Aos\Prompts\Domain\Section\PromptSectionType;

/**
 * Prompt Optimizer — ordering, redundancy removal, conceptual size trim.
 */
final class PromptOptimizer
{
    public function __construct(
        private readonly PromptSanitizer $sanitizer = new PromptSanitizer(),
    ) {}

    /**
     * @param  list<PromptSection>  $sections
     * @return list<PromptSection>
     */
    public function optimize(array $sections): array
    {
        $sections = $this->sanitizer->sanitizeSections($sections);
        $sections = $this->removeEmptyOptional($sections);
        $sections = $this->orderSections($sections);
        $sections = $this->dedupeAdjacent($sections);

        return $sections;
    }

    /**
     * Placeholder compression: truncate long optional context sections.
     *
     * @param  list<PromptSection>  $sections
     * @return list<PromptSection>
     */
    public function compress(array $sections, int $maxCharsPerOptional = 1200): array
    {
        $optional = [
            PromptSectionType::ConversationContext->value,
            PromptSectionType::ConversationSummary->value,
            PromptSectionType::MemoryContext->value,
            PromptSectionType::KnowledgeContext->value,
        ];

        $result = [];
        foreach ($sections as $section) {
            if (in_array($section->type()->value, $optional, true) && mb_strlen($section->content()) > $maxCharsPerOptional) {
                $truncated = mb_substr($section->content(), 0, $maxCharsPerOptional)."\n…[compressed]";
                $result[] = $section->withContent($truncated);
            } else {
                $result[] = $section;
            }
        }

        return $result;
    }

    /**
     * @param  list<PromptSection>  $sections
     * @return list<PromptSection>
     */
    private function removeEmptyOptional(array $sections): array
    {
        return array_values(array_filter(
            $sections,
            static fn (PromptSection $s): bool => $s->isRequired() || ! $s->isEmpty()
        ));
    }

    /**
     * @param  list<PromptSection>  $sections
     * @return list<PromptSection>
     */
    private function orderSections(array $sections): array
    {
        $map = [];
        foreach ($sections as $section) {
            $map[$section->type()->value] = $section;
        }

        $ordered = [];
        foreach (PromptSectionType::renderOrder() as $type) {
            if (isset($map[$type->value])) {
                $ordered[] = $map[$type->value];
            }
        }

        return $ordered;
    }

    /**
     * @param  list<PromptSection>  $sections
     * @return list<PromptSection>
     */
    private function dedupeAdjacent(array $sections): array
    {
        $result = [];
        $prev = null;
        foreach ($sections as $section) {
            if ($prev !== null && $prev === $section->content()) {
                continue;
            }
            $result[] = $section;
            $prev = $section->content();
        }

        return $result;
    }
}
