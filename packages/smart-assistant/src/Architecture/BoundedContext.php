<?php

declare(strict_types=1);

namespace DressnMore\SmartAssistant\Architecture;

/**
 * Official bounded contexts for Smart Assistant (contracts-first).
 */
final class BoundedContext
{
    public const ASSISTANT_CORE = 'assistant.core';
    public const CONVERSATION = 'conversation.management';
    public const CHANNEL = 'channel.management';
    public const AGENT = 'agent.management';
    public const CAMPAIGN = 'campaign.management';
    public const AUTOMATION = 'automation.management';
    public const KNOWLEDGE = 'knowledge.management';
    public const INTEGRATION = 'integration.management';
    public const AI_MODEL = 'ai.model.management';
    public const REPORTING = 'reporting';
    public const CONFIGURATION = 'configuration';

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            self::ASSISTANT_CORE,
            self::CONVERSATION,
            self::CHANNEL,
            self::AGENT,
            self::CAMPAIGN,
            self::AUTOMATION,
            self::KNOWLEDGE,
            self::INTEGRATION,
            self::AI_MODEL,
            self::REPORTING,
            self::CONFIGURATION,
        ];
    }
}
