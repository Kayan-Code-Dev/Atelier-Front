<?php

declare(strict_types=1);

namespace DressnMore\Aos\Conversation\Domain\Conversation;

/**
 * Repository port for Conversation persistence (no infrastructure coupling).
 */
interface ConversationRepositoryInterface
{
    public function save(Conversation $conversation): void;

    public function findById(ConversationId $id): ?Conversation;

    /**
     * @return list<Conversation>
     */
    public function findByTenant(TenantScopeId $tenantScopeId): array;

    public function delete(ConversationId $id): void;
}
