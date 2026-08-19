<?php

declare(strict_types=1);

namespace DressnMore\Aos\Conversation\Domain\Conversation;

use DressnMore\Aos\Conversation\Domain\Conversation\Exceptions\IllegalStateTransition;

/**
 * Guards legal Conversation status transitions.
 */
final class ConversationStateMachine
{
    /**
     * @var array<string, list<string>>
     */
    private const ALLOWED = [
        'new' => ['active', 'waiting_human', 'paused', 'closed'],
        'active' => ['waiting_customer', 'waiting_human', 'human_handling', 'paused', 'resolved', 'closed'],
        'waiting_customer' => ['active', 'waiting_human', 'human_handling', 'paused', 'resolved', 'closed'],
        'waiting_human' => ['human_handling', 'active', 'paused', 'closed'],
        'human_handling' => ['active', 'waiting_customer', 'waiting_human', 'paused', 'resolved', 'closed'],
        'paused' => ['active', 'waiting_customer', 'waiting_human', 'human_handling', 'closed'],
        'resolved' => ['closed', 'active'],
        'closed' => ['archived'],
        'archived' => [],
    ];

    public function canTransition(ConversationStatus $from, ConversationStatus $to): bool
    {
        if ($from === $to) {
            return true;
        }

        $allowed = self::ALLOWED[$from->value] ?? [];

        return in_array($to->value, $allowed, true);
    }

    /**
     * @throws IllegalStateTransition
     */
    public function assertCanTransition(ConversationStatus $from, ConversationStatus $to): void
    {
        if (! $this->canTransition($from, $to)) {
            throw IllegalStateTransition::between($from, $to);
        }
    }

    /**
     * @return list<ConversationStatus>
     */
    public function allowedTargets(ConversationStatus $from): array
    {
        return array_map(
            static fn (string $value): ConversationStatus => ConversationStatus::from($value),
            self::ALLOWED[$from->value] ?? []
        );
    }
}
