<?php

declare(strict_types=1);

namespace DressnMore\Aos\Memory\Domain\Factory;

use DateTimeImmutable;
use DressnMore\Aos\Memory\Domain\Memory\Confidence;
use DressnMore\Aos\Memory\Domain\Memory\ExpirationPolicy;
use DressnMore\Aos\Memory\Domain\Memory\ImportanceScore;
use DressnMore\Aos\Memory\Domain\Memory\MemoryId;
use DressnMore\Aos\Memory\Domain\Memory\MemoryMetadata;
use DressnMore\Aos\Memory\Domain\Memory\MemoryRecord;
use DressnMore\Aos\Memory\Domain\Memory\MemoryType;
use DressnMore\Aos\Memory\Domain\Memory\RetentionPolicy;

final class MemoryFactory
{
    /**
     * @param  list<string>  $tags
     * @param  array<string, scalar|null>  $metadata
     */
    public function create(
        MemoryType $type,
        string $content,
        string $tenantId,
        ?string $customerId = null,
        float $importance = 0.5,
        float $confidence = 0.7,
        ?ExpirationPolicy $expiration = null,
        ?RetentionPolicy $retention = null,
        array $tags = [],
        array $metadata = [],
        ?string $sourceConversationId = null,
        ?string $sourceMessageId = null,
        ?DateTimeImmutable $createdAt = null,
    ): MemoryRecord {
        $createdAt ??= new DateTimeImmutable();
        $expiration ??= $this->defaultExpiration($type);
        $retention ??= $this->defaultRetention($type);

        return new MemoryRecord(
            MemoryId::generate(),
            $type,
            $this->normalizeFact($content),
            $tenantId,
            $customerId,
            ImportanceScore::of($importance),
            Confidence::of($confidence),
            $createdAt,
            $expiration,
            $retention,
            $tags,
            MemoryMetadata::from($metadata),
            $sourceConversationId,
            $sourceMessageId,
            $expiration->expiresAt($createdAt),
        );
    }

    /**
     * Strip transcript-like noise; keep classified fact text only.
     */
    public function normalizeFact(string $content): string
    {
        $content = trim(preg_replace('/\s+/u', ' ', $content) ?? $content);
        // Drop obvious raw chat prefixes if present.
        $content = (string) preg_replace('/^(user|assistant|system)\s*:\s*/iu', '', $content);

        return $content;
    }

    private function defaultExpiration(MemoryType $type): ExpirationPolicy
    {
        return match ($type) {
            MemoryType::Working => ExpirationPolicy::Session,
            MemoryType::Conversation, MemoryType::ShortTerm => ExpirationPolicy::ShortLived,
            MemoryType::Episodic, MemoryType::Summary => ExpirationPolicy::Rolling,
            MemoryType::Preference, MemoryType::Customer, MemoryType::LongTerm, MemoryType::Business => ExpirationPolicy::LongLived,
            MemoryType::Operational => ExpirationPolicy::Rolling,
            MemoryType::Semantic => ExpirationPolicy::Permanent,
        };
    }

    private function defaultRetention(MemoryType $type): RetentionPolicy
    {
        return match ($type) {
            MemoryType::Working => RetentionPolicy::Ephemeral,
            MemoryType::Conversation, MemoryType::ShortTerm, MemoryType::Summary => RetentionPolicy::ConversationScoped,
            MemoryType::Customer, MemoryType::Preference, MemoryType::Episodic => RetentionPolicy::CustomerScoped,
            MemoryType::Business, MemoryType::Operational, MemoryType::LongTerm, MemoryType::Semantic => RetentionPolicy::TenantScoped,
        };
    }
}
