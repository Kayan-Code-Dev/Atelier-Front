<?php

declare(strict_types=1);

namespace DressnMore\Aos\TenantAi\Application;

use DressnMore\Aos\TenantAi\Contracts\MessageProviderInterface;
use DressnMore\Aos\TenantAi\Contracts\TenantAiEventPublisherInterface;
use DressnMore\Aos\TenantAi\Domain\Events\TenantAiDomainEvent;
use DressnMore\Aos\TenantAi\Domain\Message\AiMessage;
use DressnMore\Aos\TenantAi\Domain\Message\MessageRole;
use DateTimeImmutable;

final class MessageManager
{
    public function __construct(
        private readonly MessageProviderInterface $messages,
        private readonly ConversationManager $conversations,
        private readonly ?TenantAiEventPublisherInterface $events = null,
    ) {}

    /**
     * @param array<string, scalar|null> $metadata
     */
    public function append(
        string $tenantId,
        string $conversationId,
        MessageRole $role,
        string $content,
        ?int $tokenUsage = null,
        array $metadata = [],
    ): AiMessage {
        $this->conversations->getOrFail($tenantId, $conversationId);
        $message = new AiMessage(
            'msg_'.bin2hex(random_bytes(6)),
            $tenantId,
            $conversationId,
            $role,
            $content,
            $tokenUsage,
            $metadata,
            (new DateTimeImmutable())->format(DateTimeImmutable::ATOM),
        );
        $saved = $this->messages->append($message);

        $event = match ($role) {
            MessageRole::User => TenantAiDomainEvent::messageReceived([
                'tenantId' => $tenantId,
                'conversationId' => $conversationId,
                'messageId' => $saved->messageId(),
            ]),
            MessageRole::Assistant => TenantAiDomainEvent::messageGenerated([
                'tenantId' => $tenantId,
                'conversationId' => $conversationId,
                'messageId' => $saved->messageId(),
            ]),
            MessageRole::ToolCall => TenantAiDomainEvent::toolRequested([
                'tenantId' => $tenantId,
                'conversationId' => $conversationId,
                'messageId' => $saved->messageId(),
            ]),
            MessageRole::ToolResult => TenantAiDomainEvent::toolExecuted([
                'tenantId' => $tenantId,
                'conversationId' => $conversationId,
                'messageId' => $saved->messageId(),
            ]),
            default => null,
        };
        if ($event !== null) {
            $this->events?->publish($event);
        }

        return $saved;
    }

    /**
     * @return list<AiMessage>
     */
    public function history(string $tenantId, string $conversationId): array
    {
        $this->conversations->getOrFail($tenantId, $conversationId);

        return $this->messages->history($tenantId, $conversationId);
    }
}
