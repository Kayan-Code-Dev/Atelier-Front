<?php

declare(strict_types=1);

namespace DressnMore\Aos\TenantAi\Infrastructure\InMemory;

use DressnMore\Aos\TenantAi\Contracts\ConversationProviderInterface;
use DressnMore\Aos\TenantAi\Domain\Conversation\AiConversation;
use DressnMore\Aos\TenantAi\Domain\Conversation\ConversationStatus;
use RuntimeException;

/** Test/demo only. */
final class InMemoryConversationProvider implements ConversationProviderInterface
{
    /** @var array<string, AiConversation> */
    private array $items = [];

    private function key(string $tenantId, string $conversationId): string
    {
        return $tenantId.':'.$conversationId;
    }

    public function create(AiConversation $conversation): AiConversation
    {
        $this->items[$this->key($conversation->tenantId(), $conversation->conversationId())] = $conversation;

        return $conversation;
    }

    public function find(string $tenantId, string $conversationId): ?AiConversation
    {
        return $this->items[$this->key($tenantId, $conversationId)] ?? null;
    }

    public function listForTenant(string $tenantId, ?ConversationStatus $status = null): array
    {
        $out = [];
        foreach ($this->items as $conversation) {
            if ($conversation->tenantId() !== $tenantId) {
                continue;
            }
            if ($status !== null && $conversation->status() !== $status) {
                continue;
            }
            $out[] = $conversation;
        }

        return $out;
    }

    public function search(string $tenantId, string $query): array
    {
        $q = mb_strtolower($query);

        return array_values(array_filter(
            $this->listForTenant($tenantId),
            static fn (AiConversation $c): bool => str_contains(mb_strtolower($c->title()), $q),
        ));
    }

    public function rename(string $tenantId, string $conversationId, string $title): AiConversation
    {
        $current = $this->require($tenantId, $conversationId);
        $updated = $current->rename($title);
        $this->items[$this->key($tenantId, $conversationId)] = $updated;

        return $updated;
    }

    public function close(string $tenantId, string $conversationId): AiConversation
    {
        return $this->setStatus($tenantId, $conversationId, ConversationStatus::Closed);
    }

    public function archive(string $tenantId, string $conversationId): AiConversation
    {
        return $this->setStatus($tenantId, $conversationId, ConversationStatus::Archived);
    }

    private function setStatus(string $tenantId, string $conversationId, ConversationStatus $status): AiConversation
    {
        $current = $this->require($tenantId, $conversationId);
        $updated = $current->withStatus($status);
        $this->items[$this->key($tenantId, $conversationId)] = $updated;

        return $updated;
    }

    private function require(string $tenantId, string $conversationId): AiConversation
    {
        $current = $this->find($tenantId, $conversationId);
        if ($current === null) {
            throw new RuntimeException('Conversation not found.');
        }

        return $current;
    }
}
