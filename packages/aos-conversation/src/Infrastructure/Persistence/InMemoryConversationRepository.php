<?php

declare(strict_types=1);

namespace DressnMore\Aos\Conversation\Infrastructure\Persistence;

use DressnMore\Aos\Conversation\Domain\Conversation\Conversation;
use DressnMore\Aos\Conversation\Domain\Conversation\ConversationId;
use DressnMore\Aos\Conversation\Domain\Conversation\ConversationRepositoryInterface;
use DressnMore\Aos\Conversation\Domain\Conversation\TenantScopeId;

/**
 * In-memory Conversation repository for Sprint 2 (no Eloquent / DB).
 */
final class InMemoryConversationRepository implements ConversationRepositoryInterface
{
    /** @var array<string, Conversation> */
    private array $items = [];

    public function save(Conversation $conversation): void
    {
        $this->items[$conversation->id()->toString()] = $conversation;
    }

    public function findById(ConversationId $id): ?Conversation
    {
        return $this->items[$id->toString()] ?? null;
    }

    /**
     * @return list<Conversation>
     */
    public function findByTenant(TenantScopeId $tenantScopeId): array
    {
        $result = [];
        foreach ($this->items as $conversation) {
            if ($conversation->tenantScopeId()->equals($tenantScopeId)) {
                $result[] = $conversation;
            }
        }

        return $result;
    }

    public function delete(ConversationId $id): void
    {
        unset($this->items[$id->toString()]);
    }

    public function clear(): void
    {
        $this->items = [];
    }

    public function count(): int
    {
        return count($this->items);
    }
}
