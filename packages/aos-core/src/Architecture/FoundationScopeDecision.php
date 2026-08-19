<?php

declare(strict_types=1);

namespace DressnMore\Aos\Core\Architecture;

/**
 * Architecture Decision Record (in-code): Sprint 1 AOS Foundation scope.
 *
 * Decision: The AOS Foundation sprint ships only the platform kernel.
 *
 * Deliberately excluded from this sprint:
 * - Business logic and Tenant Ops tools
 * - AI / LLM providers / OpenAI
 * - WhatsApp or any channel adapter
 * - Conversation / Planner / Knowledge modules
 *
 * Rationale: Establish a stable, testable, extensible kernel with contracts,
 * module registration, events, and observability ports before product features.
 *
 * Consequences: Later sprints plug into Module Registry + Event Bus +
 * Observability contracts without rewriting the foundation.
 *
 * Status: Accepted (Architecture Freeze + Sprint 1)
 */
final class FoundationScopeDecision
{
    public const SPRINT = 'AOS Foundation';

    public const VERSION = '0.1.0-foundation';

    /**
     * @return list<string>
     */
    public static function excludedConcerns(): array
    {
        return [
            'business_logic',
            'business_tools',
            'ai_providers',
            'openai',
            'whatsapp',
            'channels',
            'planner',
            'knowledge',
            'conversations',
        ];
    }

    /**
     * @return list<string>
     */
    public static function includedPackages(): array
    {
        return [
            'dressnmore/aos-core',
            'dressnmore/aos-events',
            'dressnmore/aos-observability',
        ];
    }
}
