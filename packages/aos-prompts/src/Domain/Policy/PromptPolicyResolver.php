<?php

declare(strict_types=1);

namespace DressnMore\Aos\Prompts\Domain\Policy;

use DressnMore\Aos\Prompts\Domain\Prompt\PromptBuildRequest;
use DressnMore\Aos\Prompts\Domain\Template\PromptTemplateType;

/**
 * Resolves template / safety policy hints from request signals.
 */
final class PromptPolicyResolver
{
    public function resolveTemplateType(PromptBuildRequest $request): PromptTemplateType
    {
        if ($request->templateType() !== PromptTemplateType::GeneralConversation) {
            return $request->templateType();
        }

        $planning = $request->planningResult();
        $decision = (string) ($planning['decision'] ?? '');
        $intent = (string) ($planning['intent_kind'] ?? '');

        if ($decision === 'clarification_required' || $intent === 'unknown') {
            return PromptTemplateType::UnknownIntent;
        }
        if ($decision === 'escalation_required') {
            return PromptTemplateType::Escalation;
        }

        $tools = $request->availableTools();
        foreach ($tools as $tool) {
            if (str_contains($tool, 'Reservation')) {
                return PromptTemplateType::Reservation;
            }
            if (str_contains($tool, 'Invoice') || str_contains($tool, 'Balance')) {
                return PromptTemplateType::Invoice;
            }
            if (str_contains($tool, 'Quotation')) {
                return PromptTemplateType::Quotation;
            }
        }

        return match ($request->personaType()->value) {
            'sales_agent' => PromptTemplateType::Sales,
            'support_agent' => PromptTemplateType::Support,
            'reservation_agent' => PromptTemplateType::Reservation,
            'marketing_agent' => PromptTemplateType::FollowUp,
            default => PromptTemplateType::GeneralConversation,
        };
    }
}
