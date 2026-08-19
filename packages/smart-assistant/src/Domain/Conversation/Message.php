<?php

declare(strict_types=1);

namespace DressnMore\SmartAssistant\Domain\Conversation;

final class Message
{
    /**
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        private readonly string $id,
        private readonly string $conversationId,
        private readonly string $tenantId,
        private readonly string $direction,
        private readonly string $role,
        private readonly string $body,
        private readonly string $channelId,
        private readonly array $metadata = [],
    ) {}

    public function id(): string { return $this->id; }
    public function conversationId(): string { return $this->conversationId; }
    public function tenantId(): string { return $this->tenantId; }
    public function direction(): string { return $this->direction; }
    public function role(): string { return $this->role; }
    public function body(): string { return $this->body; }
    public function channelId(): string { return $this->channelId; }
    /** @return array<string, mixed> */
    public function metadata(): array { return $this->metadata; }
}
