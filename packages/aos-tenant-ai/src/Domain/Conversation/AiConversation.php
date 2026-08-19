<?php

declare(strict_types=1);

namespace DressnMore\Aos\TenantAi\Domain\Conversation;

final class AiConversation
{
    public function __construct(
        private readonly string $conversationId,
        private readonly string $tenantId,
        private readonly string $workspaceId,
        private readonly string $title,
        private readonly ConversationStatus $status = ConversationStatus::Open,
        private readonly ?string $createdByUserId = null,
        private readonly ?string $branchId = null,
    ) {
        if ($conversationId === '' || $tenantId === '' || $workspaceId === '') {
            throw new \InvalidArgumentException('conversationId, tenantId, workspaceId are required.');
        }
    }

    public function conversationId(): string { return $this->conversationId; }
    public function tenantId(): string { return $this->tenantId; }
    public function workspaceId(): string { return $this->workspaceId; }
    public function title(): string { return $this->title; }
    public function status(): ConversationStatus { return $this->status; }
    public function createdByUserId(): ?string { return $this->createdByUserId; }
    public function branchId(): ?string { return $this->branchId; }

    public function rename(string $title): self
    {
        return new self(
            $this->conversationId,
            $this->tenantId,
            $this->workspaceId,
            $title,
            $this->status,
            $this->createdByUserId,
            $this->branchId,
        );
    }

    public function withStatus(ConversationStatus $status): self
    {
        return new self(
            $this->conversationId,
            $this->tenantId,
            $this->workspaceId,
            $this->title,
            $status,
            $this->createdByUserId,
            $this->branchId,
        );
    }

    public function belongsToTenant(string $tenantId): bool
    {
        return $this->tenantId === $tenantId;
    }
}
