<?php

declare(strict_types=1);

namespace DressnMore\Aos\Prompts\Domain\Context;

use DressnMore\Aos\Prompts\Domain\Prompt\PromptBuildRequest;
use DressnMore\Aos\Prompts\Domain\Section\PromptSection;
use DressnMore\Aos\Prompts\Domain\Section\PromptSectionType;

/**
 * Injects opaque conversation / planning context into section payloads.
 * Memory and Knowledge remain placeholders until future modules.
 */
final class PromptContextInjector
{
    /**
     * @return list<PromptSection>
     */
    public function inject(PromptBuildRequest $request): array
    {
        return [
            new PromptSection(
                PromptSectionType::ConversationContext,
                $request->conversationContext() ?? 'No additional conversation context.',
                false
            ),
            new PromptSection(
                PromptSectionType::ConversationSummary,
                $request->conversationSummary() ?? 'No conversation summary.',
                false
            ),
            new PromptSection(
                PromptSectionType::MemoryContext,
                '[Memory placeholder — future module]',
                false
            ),
            new PromptSection(
                PromptSectionType::KnowledgeContext,
                '[Knowledge placeholder — future module]',
                false
            ),
            new PromptSection(
                PromptSectionType::TenantInstructions,
                $request->tenantInstructions() ?? 'No tenant-specific instructions.',
                false
            ),
        ];
    }
}
