<?php

declare(strict_types=1);

namespace DressnMore\SmartAssistant\Domain\Conversation;

final class ConversationThread
{
    /**
     * @param list<string> $messageIds
     */
    public function __construct(
        private readonly string $id,
        private readonly string $conversationId,
        private readonly string $tenantId,
        private readonly array $messageIds = [],
    ) {}

    public function id(): string { return $this->id; }
    public function conversationId(): string { return $this->conversationId; }
    public function tenantId(): string { return $this->tenantId; }
    /** @return list<string> */
    public function messageIds(): array { return $this->messageIds; }
}
