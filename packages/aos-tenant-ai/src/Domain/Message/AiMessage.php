<?php

declare(strict_types=1);

namespace DressnMore\Aos\TenantAi\Domain\Message;

final class AiMessage
{
    /**
     * @param array<string, scalar|null> $metadata
     */
    public function __construct(
        private readonly string $messageId,
        private readonly string $tenantId,
        private readonly string $conversationId,
        private readonly MessageRole $role,
        private readonly string $content,
        private readonly ?int $tokenUsage = null,
        private readonly array $metadata = [],
        private readonly string $createdAt = '',
    ) {}

    public function messageId(): string { return $this->messageId; }
    public function tenantId(): string { return $this->tenantId; }
    public function conversationId(): string { return $this->conversationId; }
    public function role(): MessageRole { return $this->role; }
    public function content(): string { return $this->content; }
    public function tokenUsage(): ?int { return $this->tokenUsage; }
    /** @return array<string, scalar|null> */
    public function metadata(): array { return $this->metadata; }
    public function createdAt(): string { return $this->createdAt; }

    public function belongsToTenant(string $tenantId): bool
    {
        return $this->tenantId === $tenantId;
    }
}
