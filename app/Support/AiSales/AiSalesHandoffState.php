<?php

declare(strict_types=1);

namespace App\Support\AiSales;

use DressnMore\Aos\Conversation\Domain\Conversation\ConversationOwnership;

/**
 * Handoff state for DressnMore platform sales conversations.
 * Maps onto existing AOS ConversationOwnership — not a second engine.
 */
enum AiSalesHandoffState: string
{
    case AiActive = 'AI_ACTIVE';
    case HumanRequested = 'HUMAN_REQUESTED';
    case HumanActive = 'HUMAN_ACTIVE';
    case ReturnedToAi = 'RETURNED_TO_AI';
    case Closed = 'CLOSED';

    public static function fromStored(?string $value): self
    {
        $raw = strtoupper(trim((string) $value));

        return self::tryFrom($raw) ?? match ($raw) {
            'HUMAN_HANDOFF' => self::HumanActive,
            default => self::AiActive,
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $case): string => $case->value, self::cases());
    }

    public function uiStatus(): string
    {
        return match ($this) {
            self::HumanRequested, self::HumanActive => 'HUMAN_HANDOFF',
            self::Closed => 'CLOSED',
            default => 'AI_ACTIVE',
        };
    }

    public function ownership(): ConversationOwnership
    {
        return match ($this) {
            self::AiActive, self::ReturnedToAi => ConversationOwnership::AI,
            self::HumanRequested => ConversationOwnership::SharedAssist,
            self::HumanActive => ConversationOwnership::Human,
            self::Closed => ConversationOwnership::System,
        };
    }

    public function allowsAutonomousAi(): bool
    {
        return $this === self::AiActive || $this === self::ReturnedToAi;
    }
}
