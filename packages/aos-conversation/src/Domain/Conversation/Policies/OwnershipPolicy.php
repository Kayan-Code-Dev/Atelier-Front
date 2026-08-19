<?php

declare(strict_types=1);

namespace DressnMore\Aos\Conversation\Domain\Conversation\Policies;

use DressnMore\Aos\Conversation\Domain\Conversation\ConversationOwnership;
use DressnMore\Aos\Conversation\Domain\Conversation\ConversationStatus;
use DressnMore\Aos\Conversation\Domain\Conversation\Exceptions\OwnershipPolicyViolation;

/**
 * Ownership change rules for the Conversation aggregate.
 */
final class OwnershipPolicy
{
    /**
     * @throws OwnershipPolicyViolation
     */
    public function assertCanChange(
        ConversationOwnership $from,
        ConversationOwnership $to,
        ConversationStatus $status,
    ): void {
        if ($from === $to) {
            return;
        }

        if ($status->isTerminal()) {
            throw OwnershipPolicyViolation::cannotAssign($from, $to, 'conversation is terminal');
        }

        if ($status === ConversationStatus::Archived) {
            throw OwnershipPolicyViolation::cannotAssign($from, $to, 'conversation is archived');
        }

        // System may only own briefly at creation/automation handoff.
        if ($to === ConversationOwnership::System && $from !== ConversationOwnership::System) {
            throw OwnershipPolicyViolation::cannotAssign($from, $to, 'cannot transfer ongoing work to System');
        }

        // Returning to AI must come from Human or SharedAssist (or System at start).
        if ($to === ConversationOwnership::AI
            && ! in_array($from, [ConversationOwnership::Human, ConversationOwnership::SharedAssist, ConversationOwnership::System], true)
        ) {
            throw OwnershipPolicyViolation::cannotAssign($from, $to, 'only Human/SharedAssist/System may return to AI');
        }
    }

    public function ownerForAssignHuman(): ConversationOwnership
    {
        return ConversationOwnership::Human;
    }

    public function ownerForReturnToAi(): ConversationOwnership
    {
        return ConversationOwnership::AI;
    }

    public function ownerForSharedAssist(): ConversationOwnership
    {
        return ConversationOwnership::SharedAssist;
    }
}
