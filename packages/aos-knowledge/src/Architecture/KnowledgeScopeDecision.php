<?php

declare(strict_types=1);

namespace DressnMore\Aos\Knowledge\Architecture;

final class KnowledgeScopeDecision
{
    public const SPRINT = 'Knowledge Engine';

    public const VERSION = '0.9.0';

    /**
     * @return list<string>
     */
    public static function excludedConcerns(): array
    {
        return [
            'openai',
            'claude',
            'gemini',
            'ai_providers',
            'embeddings',
            'vector_database',
            'llm',
            'business_tools',
            'planner',
            'whatsapp',
            'messenger',
            'database',
            'eloquent',
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
            'dressnmore/aos-knowledge',
        ];
    }
}
