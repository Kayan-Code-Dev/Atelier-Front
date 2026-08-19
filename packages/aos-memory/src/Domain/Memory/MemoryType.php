<?php

declare(strict_types=1);

namespace DressnMore\Aos\Memory\Domain\Memory;

/**
 * Classifiable memory kinds. Semantic remains a placeholder for future modules.
 */
enum MemoryType: string
{
    case Working = 'working';
    case Conversation = 'conversation';
    case ShortTerm = 'short_term';
    case LongTerm = 'long_term';
    case Customer = 'customer';
    case Business = 'business';
    case Preference = 'preference';
    case Operational = 'operational';
    case Episodic = 'episodic';
    case Summary = 'summary';
    case Semantic = 'semantic'; // Placeholder — future knowledge/embeddings module

    public function isDurable(): bool
    {
        return match ($this) {
            self::Working, self::Conversation, self::ShortTerm => false,
            default => true,
        };
    }

    public function allowsRawMessageContent(): bool
    {
        // Architectural constraint: never persist raw messages as durable memory.
        return false;
    }
}
