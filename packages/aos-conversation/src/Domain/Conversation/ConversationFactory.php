<?php

declare(strict_types=1);

namespace DressnMore\Aos\Conversation\Domain\Conversation;

/**
 * Creates Conversation aggregates with consistent defaults.
 */
final class ConversationFactory
{
    public function create(
        TenantScopeId $tenantScopeId,
        ConversationOwnership $ownership = ConversationOwnership::AI,
        ?ConversationId $id = null,
    ): Conversation {
        return Conversation::startNew(
            $id ?? ConversationId::generate(),
            $tenantScopeId,
            $ownership,
        );
    }

    public function createForTenant(string $tenantScope, ConversationOwnership $ownership = ConversationOwnership::AI): Conversation
    {
        return $this->create(TenantScopeId::fromString($tenantScope), $ownership);
    }
}
