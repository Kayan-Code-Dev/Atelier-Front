<?php

declare(strict_types=1);

namespace DressnMore\Aos\TenantAi\Domain\Session;

final class AiSession
{
    public function __construct(
        private readonly string $tenantId,
        private readonly string $workspaceId,
        private readonly ?string $conversationId = null,
        private readonly ?string $userId = null,
        private readonly ?string $branchId = null,
        private readonly ?string $currentIntent = null,
        private readonly ?string $currentCapability = null,
        private readonly ?string $currentTool = null,
    ) {}

    public function tenantId(): string { return $this->tenantId; }
    public function workspaceId(): string { return $this->workspaceId; }
    public function conversationId(): ?string { return $this->conversationId; }
    public function userId(): ?string { return $this->userId; }
    public function branchId(): ?string { return $this->branchId; }
    public function currentIntent(): ?string { return $this->currentIntent; }
    public function currentCapability(): ?string { return $this->currentCapability; }
    public function currentTool(): ?string { return $this->currentTool; }

    public function withConversation(?string $conversationId): self
    {
        return new self(
            $this->tenantId,
            $this->workspaceId,
            $conversationId,
            $this->userId,
            $this->branchId,
            $this->currentIntent,
            $this->currentCapability,
            $this->currentTool,
        );
    }

    public function withFocus(?string $intent, ?string $capability, ?string $tool): self
    {
        return new self(
            $this->tenantId,
            $this->workspaceId,
            $this->conversationId,
            $this->userId,
            $this->branchId,
            $intent,
            $capability,
            $tool,
        );
    }
}
