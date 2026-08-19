<?php

declare(strict_types=1);

namespace DressnMore\Aos\TenantAi\Contracts;

use DressnMore\Aos\TenantAi\Domain\Conversation\AiConversation;
use DressnMore\Aos\TenantAi\Domain\Conversation\ConversationStatus;

interface ConversationProviderInterface
{
    public function create(AiConversation $conversation): AiConversation;

    public function find(string $tenantId, string $conversationId): ?AiConversation;

    /**
     * @return list<AiConversation>
     */
    public function listForTenant(string $tenantId, ?ConversationStatus $status = null): array;

    /**
     * @return list<AiConversation>
     */
    public function search(string $tenantId, string $query): array;

    public function rename(string $tenantId, string $conversationId, string $title): AiConversation;

    public function close(string $tenantId, string $conversationId): AiConversation;

    public function archive(string $tenantId, string $conversationId): AiConversation;
}
