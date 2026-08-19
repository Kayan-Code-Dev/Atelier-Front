<?php

declare(strict_types=1);

namespace DressnMore\SmartAssistant\Architecture;

/**
 * Declares how new agents/channels/models/integrations/capabilities are added
 * without modifying core (Open/Closed).
 */
final class ExtensionPoints
{
    public const AGENT = 'agent';
    public const CHANNEL = 'channel';
    public const AI_MODEL = 'ai_model';
    public const INTEGRATION = 'integration';
    public const CAPABILITY = 'capability';
    public const AUTOMATION = 'automation';
    public const KNOWLEDGE_PROVIDER = 'knowledge_provider';

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            self::AGENT,
            self::CHANNEL,
            self::AI_MODEL,
            self::INTEGRATION,
            self::CAPABILITY,
            self::AUTOMATION,
            self::KNOWLEDGE_PROVIDER,
        ];
    }
}
