<?php

declare(strict_types=1);

namespace DressnMore\Aos\Knowledge\Domain\Knowledge;

use DateTimeImmutable;
use DressnMore\Aos\Knowledge\Domain\Collection\CollectionId;
use DressnMore\Aos\Knowledge\Domain\Metadata\KnowledgeMetadata;
use DressnMore\Aos\Knowledge\Domain\Source\KnowledgeSourceType;
use DressnMore\Aos\Knowledge\Domain\Source\SourceId;
use InvalidArgumentException;
use LogicException;

/**
 * Knowledge document aggregate — provider-agnostic content unit.
 */
final class KnowledgeDocument
{
    /**
     * @param  list<string>  $tags
     */
    public function __construct(
        private readonly KnowledgeId $id,
        private readonly KnowledgeType $type,
        private readonly CollectionId $collectionId,
        private readonly string $title,
        private readonly string $body,
        private readonly KnowledgeLifecycleStatus $status,
        private readonly KnowledgeVersion $version,
        private readonly KnowledgeSourceType $sourceType,
        private readonly ?SourceId $sourceId,
        private readonly string $owner,
        private readonly string $language,
        private readonly VisibilityPolicy $visibility,
        private readonly RetentionPolicy $retention,
        private readonly array $tags,
        private readonly KnowledgeMetadata $metadata,
        private readonly DateTimeImmutable $createdAt,
        private readonly DateTimeImmutable $updatedAt,
        private readonly ?string $tenantId = null,
        private readonly float $importance = 0.5,
        private readonly float $confidence = 0.7,
        private readonly float $businessPriority = 0.5,
        private readonly int $popularity = 0,
    ) {
        if (trim($this->title) === '' || trim($this->body) === '') {
            throw new InvalidArgumentException('Knowledge title and body are required.');
        }
        if ($this->type === KnowledgeType::Platform && $this->tenantId !== null) {
            throw new InvalidArgumentException('Platform knowledge cannot be tenant-scoped.');
        }
        if (in_array($this->type, [KnowledgeType::Tenant, KnowledgeType::Customer, KnowledgeType::Business], true)
            && ($this->tenantId === null || $this->tenantId === '')
            && $this->visibility !== VisibilityPolicy::PublicGlobal) {
            // Tenant knowledge should typically carry tenantId unless explicitly global visibility for platform FAQ-like content.
        }
    }

    public function id(): KnowledgeId
    {
        return $this->id;
    }

    public function type(): KnowledgeType
    {
        return $this->type;
    }

    public function collectionId(): CollectionId
    {
        return $this->collectionId;
    }

    public function title(): string
    {
        return $this->title;
    }

    public function body(): string
    {
        return $this->body;
    }

    public function status(): KnowledgeLifecycleStatus
    {
        return $this->status;
    }

    public function version(): KnowledgeVersion
    {
        return $this->version;
    }

    public function sourceType(): KnowledgeSourceType
    {
        return $this->sourceType;
    }

    public function sourceId(): ?SourceId
    {
        return $this->sourceId;
    }

    public function owner(): string
    {
        return $this->owner;
    }

    public function language(): string
    {
        return $this->language;
    }

    public function visibility(): VisibilityPolicy
    {
        return $this->visibility;
    }

    public function retention(): RetentionPolicy
    {
        return $this->retention;
    }

    /**
     * @return list<string>
     */
    public function tags(): array
    {
        return $this->tags;
    }

    public function metadata(): KnowledgeMetadata
    {
        return $this->metadata;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function tenantId(): ?string
    {
        return $this->tenantId;
    }

    public function importance(): float
    {
        return $this->importance;
    }

    public function confidence(): float
    {
        return $this->confidence;
    }

    public function businessPriority(): float
    {
        return $this->businessPriority;
    }

    public function popularity(): int
    {
        return $this->popularity;
    }

    public function isPublished(): bool
    {
        return $this->status->isRetrievable();
    }

    public function searchableText(): string
    {
        return mb_strtolower($this->title.' '.$this->body.' '.implode(' ', $this->tags));
    }

    public function withStatus(KnowledgeLifecycleStatus $status): self
    {
        if (! $this->status->canTransitionTo($status)) {
            throw new LogicException(sprintf(
                'Invalid lifecycle transition [%s] → [%s].',
                $this->status->value,
                $status->value
            ));
        }

        return $this->copy(status: $status, updatedAt: new DateTimeImmutable());
    }

    public function withContent(string $title, string $body, string $updatedBy = 'aos.knowledge'): self
    {
        return $this->copy(
            title: $title,
            body: $body,
            version: $this->version->bumpMinor($updatedBy),
            updatedAt: new DateTimeImmutable(),
        );
    }

    public function bumpPopularity(): self
    {
        return $this->copy(popularity: $this->popularity + 1, updatedAt: $this->updatedAt);
    }

    public function withMetadata(KnowledgeMetadata $metadata): self
    {
        return $this->copy(metadata: $metadata, updatedAt: new DateTimeImmutable());
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id->toString(),
            'type' => $this->type->value,
            'collection_id' => $this->collectionId->toString(),
            'title' => $this->title,
            'body' => $this->body,
            'status' => $this->status->value,
            'version' => $this->version->version(),
            'source_type' => $this->sourceType->value,
            'source_id' => $this->sourceId?->toString(),
            'owner' => $this->owner,
            'language' => $this->language,
            'visibility' => $this->visibility->value,
            'retention' => $this->retention->value,
            'tags' => $this->tags,
            'metadata' => $this->metadata->all(),
            'tenant_id' => $this->tenantId,
            'importance' => $this->importance,
            'confidence' => $this->confidence,
            'business_priority' => $this->businessPriority,
            'popularity' => $this->popularity,
            'created_at' => $this->createdAt->format(DATE_ATOM),
            'updated_at' => $this->updatedAt->format(DATE_ATOM),
        ];
    }

    /**
     * @param  list<string>|null  $tags
     */
    private function copy(
        ?string $title = null,
        ?string $body = null,
        ?KnowledgeLifecycleStatus $status = null,
        ?KnowledgeVersion $version = null,
        ?DateTimeImmutable $updatedAt = null,
        ?int $popularity = null,
        ?array $tags = null,
        ?KnowledgeMetadata $metadata = null,
    ): self {
        return new self(
            $this->id,
            $this->type,
            $this->collectionId,
            $title ?? $this->title,
            $body ?? $this->body,
            $status ?? $this->status,
            $version ?? $this->version,
            $this->sourceType,
            $this->sourceId,
            $this->owner,
            $this->language,
            $this->visibility,
            $this->retention,
            $tags ?? $this->tags,
            $metadata ?? $this->metadata,
            $this->createdAt,
            $updatedAt ?? $this->updatedAt,
            $this->tenantId,
            $this->importance,
            $this->confidence,
            $this->businessPriority,
            $popularity ?? $this->popularity,
        );
    }
}
