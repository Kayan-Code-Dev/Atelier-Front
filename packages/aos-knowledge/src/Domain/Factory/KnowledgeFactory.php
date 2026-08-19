<?php

declare(strict_types=1);

namespace DressnMore\Aos\Knowledge\Domain\Factory;

use DateTimeImmutable;
use DressnMore\Aos\Knowledge\Domain\Collection\CollectionId;
use DressnMore\Aos\Knowledge\Domain\Knowledge\KnowledgeDocument;
use DressnMore\Aos\Knowledge\Domain\Knowledge\KnowledgeId;
use DressnMore\Aos\Knowledge\Domain\Knowledge\KnowledgeLifecycleStatus;
use DressnMore\Aos\Knowledge\Domain\Knowledge\KnowledgeType;
use DressnMore\Aos\Knowledge\Domain\Knowledge\KnowledgeVersion;
use DressnMore\Aos\Knowledge\Domain\Knowledge\RetentionPolicy;
use DressnMore\Aos\Knowledge\Domain\Knowledge\VisibilityPolicy;
use DressnMore\Aos\Knowledge\Domain\Metadata\KnowledgeMetadata;
use DressnMore\Aos\Knowledge\Domain\Source\KnowledgeSourceType;
use DressnMore\Aos\Knowledge\Domain\Source\SourceId;

final class KnowledgeFactory
{
    /**
     * @param  list<string>  $tags
     * @param  array<string, scalar|null>  $metadata
     */
    public function create(
        KnowledgeType $type,
        CollectionId $collectionId,
        string $title,
        string $body,
        KnowledgeSourceType $sourceType = KnowledgeSourceType::ManualEntry,
        ?SourceId $sourceId = null,
        string $owner = 'system',
        string $language = 'ar',
        VisibilityPolicy $visibility = VisibilityPolicy::TenantOnly,
        RetentionPolicy $retention = RetentionPolicy::Standard,
        array $tags = [],
        array $metadata = [],
        ?string $tenantId = null,
        float $importance = 0.5,
        float $confidence = 0.7,
        float $businessPriority = 0.5,
        KnowledgeLifecycleStatus $status = KnowledgeLifecycleStatus::Draft,
    ): KnowledgeDocument {
        $now = new DateTimeImmutable();

        return new KnowledgeDocument(
            KnowledgeId::generate(),
            $type,
            $collectionId,
            trim($title),
            trim($body),
            $status,
            KnowledgeVersion::initial(),
            $sourceType,
            $sourceId,
            $owner,
            $language,
            $visibility,
            $retention,
            $tags,
            KnowledgeMetadata::from($metadata),
            $now,
            $now,
            $tenantId,
            max(0.0, min(1.0, $importance)),
            max(0.0, min(1.0, $confidence)),
            max(0.0, min(1.0, $businessPriority)),
        );
    }
}
