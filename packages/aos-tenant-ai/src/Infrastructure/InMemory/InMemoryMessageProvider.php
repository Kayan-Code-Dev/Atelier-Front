<?php

declare(strict_types=1);

namespace DressnMore\Aos\TenantAi\Infrastructure\InMemory;

use DressnMore\Aos\TenantAi\Contracts\MessageProviderInterface;
use DressnMore\Aos\TenantAi\Domain\Message\AiMessage;

/** Test/demo only. */
final class InMemoryMessageProvider implements MessageProviderInterface
{
    /** @var array<string, list<AiMessage>> */
    private array $byConversation = [];

    public function append(AiMessage $message): AiMessage
    {
        $key = $message->tenantId().':'.$message->conversationId();
        $this->byConversation[$key][] = $message;

        return $message;
    }

    public function history(string $tenantId, string $conversationId): array
    {
        return $this->byConversation[$tenantId.':'.$conversationId] ?? [];
    }
}
