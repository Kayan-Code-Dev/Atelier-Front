<?php

declare(strict_types=1);

namespace DressnMore\Aos\Memory\Domain\Memory;

use DateTimeImmutable;
use InvalidArgumentException;

/**
 * Immutable classified memory fact — never stores raw chat transcripts as durable content.
 */
final class MemoryRecord
{
    /**
     * @param  list<string>  $tags
     */
    public function __construct(
        private readonly MemoryId $id,
        private readonly MemoryType $type,
        private readonly string $content,
        private readonly string $tenantId,
        private readonly ?string $customerId,
        private readonly ImportanceScore $importance,
        private readonly Confidence $confidence,
        private readonly DateTimeImmutable $createdAt,
        private readonly ExpirationPolicy $expirationPolicy,
        private readonly RetentionPolicy $retentionPolicy,
        private readonly array $tags = [],
        private readonly MemoryMetadata $metadata = new MemoryMetadata(),
        private readonly ?string $sourceConversationId = null,
        private readonly ?string $sourceMessageId = null,
        private readonly ?DateTimeImmutable $expiresAt = null,
        private readonly int $accessCount = 0,
        private readonly ?DateTimeImmutable $lastAccessedAt = null,
        private readonly bool $discarded = false,
    ) {
        if (trim($this->content) === '') {
            throw new InvalidArgumentException('Memory content cannot be empty.');
        }
        if ($this->tenantId === '') {
            throw new InvalidArgumentException('Memory tenantId is required for isolation.');
        }
        if ($this->type->allowsRawMessageContent()) {
            throw new InvalidArgumentException('Memory type must not store raw message content.');
        }
    }

    public function id(): MemoryId
    {
        return $this->id;
    }

    public function type(): MemoryType
    {
        return $this->type;
    }

    public function content(): string
    {
        return $this->content;
    }

    public function tenantId(): string
    {
        return $this->tenantId;
    }

    public function customerId(): ?string
    {
        return $this->customerId;
    }

    public function importance(): ImportanceScore
    {
        return $this->importance;
    }

    public function confidence(): Confidence
    {
        return $this->confidence;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function expirationPolicy(): ExpirationPolicy
    {
        return $this->expirationPolicy;
    }

    public function retentionPolicy(): RetentionPolicy
    {
        return $this->retentionPolicy;
    }

    /**
     * @return list<string>
     */
    public function tags(): array
    {
        return $this->tags;
    }

    public function metadata(): MemoryMetadata
    {
        return $this->metadata;
    }

    public function sourceConversationId(): ?string
    {
        return $this->sourceConversationId;
    }

    public function sourceMessageId(): ?string
    {
        return $this->sourceMessageId;
    }

    public function expiresAt(): ?DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function accessCount(): int
    {
        return $this->accessCount;
    }

    public function lastAccessedAt(): ?DateTimeImmutable
    {
        return $this->lastAccessedAt;
    }

    public function isDiscarded(): bool
    {
        return $this->discarded;
    }

    public function isExpired(?DateTimeImmutable $now = null): bool
    {
        if ($this->expiresAt === null) {
            return false;
        }

        return $this->expiresAt <= ($now ?? new DateTimeImmutable());
    }

    public function belongsToTenant(string $tenantId): bool
    {
        return $this->tenantId === $tenantId;
    }

    public function belongsToCustomer(?string $customerId): bool
    {
        if ($customerId === null) {
            return $this->customerId === null;
        }

        return $this->customerId === $customerId;
    }

    public function withContent(string $content): self
    {
        return $this->copy(content: $content);
    }

    public function withImportance(ImportanceScore $importance): self
    {
        return $this->copy(importance: $importance);
    }

    public function withType(MemoryType $type): self
    {
        return $this->copy(type: $type);
    }

    public function touchAccessed(?DateTimeImmutable $at = null): self
    {
        return $this->copy(
            accessCount: $this->accessCount + 1,
            lastAccessedAt: $at ?? new DateTimeImmutable(),
        );
    }

    public function discard(): self
    {
        return $this->copy(discarded: true);
    }

    public function fingerprint(): string
    {
        return hash('sha256', mb_strtolower(trim($this->tenantId.'|'.($this->customerId ?? '').'|'.$this->type->value.'|'.$this->content)));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id->toString(),
            'type' => $this->type->value,
            'content' => $this->content,
            'tenant_id' => $this->tenantId,
            'customer_id' => $this->customerId,
            'importance' => $this->importance->value(),
            'confidence' => $this->confidence->value(),
            'created_at' => $this->createdAt->format(DATE_ATOM),
            'expires_at' => $this->expiresAt?->format(DATE_ATOM),
            'expiration_policy' => $this->expirationPolicy->value,
            'retention_policy' => $this->retentionPolicy->value,
            'tags' => $this->tags,
            'metadata' => $this->metadata->all(),
            'source_conversation_id' => $this->sourceConversationId,
            'source_message_id' => $this->sourceMessageId,
            'access_count' => $this->accessCount,
            'discarded' => $this->discarded,
        ];
    }

    /**
     * @param  list<string>|null  $tags
     */
    private function copy(
        ?MemoryType $type = null,
        ?string $content = null,
        ?ImportanceScore $importance = null,
        ?Confidence $confidence = null,
        ?array $tags = null,
        ?MemoryMetadata $metadata = null,
        ?int $accessCount = null,
        ?DateTimeImmutable $lastAccessedAt = null,
        ?bool $discarded = null,
    ): self {
        return new self(
            $this->id,
            $type ?? $this->type,
            $content ?? $this->content,
            $this->tenantId,
            $this->customerId,
            $importance ?? $this->importance,
            $confidence ?? $this->confidence,
            $this->createdAt,
            $this->expirationPolicy,
            $this->retentionPolicy,
            $tags ?? $this->tags,
            $metadata ?? $this->metadata,
            $this->sourceConversationId,
            $this->sourceMessageId,
            $this->expiresAt,
            $accessCount ?? $this->accessCount,
            $lastAccessedAt ?? $this->lastAccessedAt,
            $discarded ?? $this->discarded,
        );
    }
}
