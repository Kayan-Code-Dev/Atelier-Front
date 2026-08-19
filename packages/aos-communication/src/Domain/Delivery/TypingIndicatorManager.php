<?php

declare(strict_types=1);

namespace DressnMore\Aos\Communication\Domain\Delivery;

final class TypingIndicatorManager
{
    /** @var array<string, bool> */
    private array $typingByConversation = [];

    public function setTyping(string $conversationId, bool $typing): void
    {
        $this->typingByConversation[$conversationId] = $typing;
    }

    public function isTyping(string $conversationId): bool
    {
        return $this->typingByConversation[$conversationId] ?? false;
    }
}
