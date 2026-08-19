<?php

declare(strict_types=1);

namespace DressnMore\SmartAssistant\Domain\Conversation;

final class Conversation
{
    public function __construct(
        private readonly string $id,
        private readonly string $tenantId,
        private readonly string $channelId,
        private readonly ?string $customerId = null,
        private readonly string $status = 'open',
        private readonly ?string $agentId = null,
        private readonly ?string $threadId = null,
    ) {}

    public function id(): string { return $this->id; }
    public function tenantId(): string { return $this->tenantId; }
    public function channelId(): string { return $this->channelId; }
    public function customerId(): ?string { return $this->customerId; }
    public function status(): string { return $this->status; }
    public function agentId(): ?string { return $this->agentId; }
    public function threadId(): ?string { return $this->threadId; }
}
