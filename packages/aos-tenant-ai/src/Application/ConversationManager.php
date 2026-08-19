<?php

declare(strict_types=1);

namespace DressnMore\Aos\TenantAi\Application;

use DressnMore\Aos\TenantAi\Contracts\ConversationProviderInterface;
use DressnMore\Aos\TenantAi\Contracts\TenantAiEventPublisherInterface;
use DressnMore\Aos\TenantAi\Domain\Conversation\AiConversation;
use DressnMore\Aos\TenantAi\Domain\Conversation\ConversationStatus;
use DressnMore\Aos\TenantAi\Domain\Events\TenantAiDomainEvent;
use DressnMore\Aos\TenantAi\Domain\Policies\TenantAiPolicies;

final class ConversationManager
{
    public function __construct(
        private readonly ConversationProviderInterface $conversations,
        private readonly TenantAiPolicies $policies = new TenantAiPolicies(),
        private readonly ?TenantAiEventPublisherInterface $events = null,
    ) {}

    public function start(string $tenantId, string $workspaceId, string $title, ?string $userId = null, ?string $branchId = null): AiConversation
    {
        $this->policies->assertTenantExists($tenantId);
        $conversation = new AiConversation(
            'conv_'.bin2hex(random_bytes(6)),
            $tenantId,
            $workspaceId,
            $title,
            ConversationStatus::Open,
            $userId,
            $branchId,
        );
        $created = $this->conversations->create($conversation);
        $this->events?->publish(TenantAiDomainEvent::conversationStarted([
            'tenantId' => $tenantId,
            'conversationId' => $created->conversationId(),
        ]));

        return $created;
    }

    public function getOrFail(string $tenantId, string $conversationId): AiConversation
    {
        $conversation = $this->conversations->find($tenantId, $conversationId);
        $this->policies->assertConversationExists($conversation !== null);
        $this->policies->assertConversationIsolation($tenantId, $conversation);

        return $conversation;
    }

    /**
     * @return list<AiConversation>
     */
    public function list(string $tenantId, ?ConversationStatus $status = null): array
    {
        return $this->conversations->listForTenant($tenantId, $status);
    }

    /**
     * @return list<AiConversation>
     */
    public function search(string $tenantId, string $query): array
    {
        return $this->conversations->search($tenantId, $query);
    }

    public function rename(string $tenantId, string $conversationId, string $title): AiConversation
    {
        $this->getOrFail($tenantId, $conversationId);

        return $this->conversations->rename($tenantId, $conversationId, $title);
    }

    public function close(string $tenantId, string $conversationId): AiConversation
    {
        $this->getOrFail($tenantId, $conversationId);
        $closed = $this->conversations->close($tenantId, $conversationId);
        $this->events?->publish(TenantAiDomainEvent::conversationClosed([
            'tenantId' => $tenantId,
            'conversationId' => $conversationId,
        ]));

        return $closed;
    }

    public function archive(string $tenantId, string $conversationId): AiConversation
    {
        $this->getOrFail($tenantId, $conversationId);

        return $this->conversations->archive($tenantId, $conversationId);
    }
}
