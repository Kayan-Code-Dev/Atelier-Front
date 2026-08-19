<?php

declare(strict_types=1);

namespace DressnMore\SmartAssistant\Contracts\Conversation;

use DressnMore\SmartAssistant\Domain\Conversation\Conversation;
use DressnMore\SmartAssistant\Domain\Conversation\Message;

interface ConversationManagerInterface
{
    public function open(string $tenantId, string $channelId, ?string $customerId = null): Conversation;

    public function close(string $tenantId, string $conversationId): void;

    public function appendMessage(Message $message): void;
}
