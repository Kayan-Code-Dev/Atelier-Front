<?php

declare(strict_types=1);

namespace DressnMore\Aos\Conversation\Architecture;

/**
 * ADR: Sprint 2 Conversation Engine scope.
 *
 * In scope: Conversation aggregate lifecycle, ownership, sessions, timeline,
 * state machine, domain events, repository ports, in-memory adapter.
 *
 * Out of scope: OpenAI, WhatsApp, Business Tools, Knowledge, Planner,
 * Tenant business logic, Eloquent/DB, Controllers/APIs.
 */
final class ConversationScopeDecision
{
    public const SPRINT = 'Conversation Engine';

    public const VERSION = '0.2.0';

    /**
     * @return list<string>
     */
    public static function excludedConcerns(): array
    {
        return [
            'openai',
            'ai_providers',
            'whatsapp',
            'channels',
            'business_tools',
            'knowledge',
            'planner',
            'tenant_business',
            'eloquent',
            'database',
            'controllers',
            'http_apis',
        ];
    }

    /**
     * @return list<string>
     */
    public static function includedPackages(): array
    {
        return [
            'dressnmore/aos-conversation',
        ];
    }
}
