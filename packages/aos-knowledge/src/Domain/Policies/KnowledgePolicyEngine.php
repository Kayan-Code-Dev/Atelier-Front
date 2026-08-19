<?php

declare(strict_types=1);

namespace DressnMore\Aos\Knowledge\Domain\Policies;

use DressnMore\Aos\Knowledge\Domain\Collection\KnowledgeCollection;
use DressnMore\Aos\Knowledge\Domain\Knowledge\KnowledgeDocument;
use DressnMore\Aos\Knowledge\Domain\Knowledge\KnowledgeLifecycleStatus;
use DressnMore\Aos\Knowledge\Domain\Knowledge\VisibilityPolicy;

/**
 * Visibility, access, publishing, versioning, retention, tenant isolation, compliance.
 */
final class KnowledgePolicyEngine
{
    public function canRetrieve(KnowledgeDocument $document, ?string $tenantId, ?string $ownerId = null): bool
    {
        if (! $document->status()->isRetrievable()) {
            return false;
        }

        return $this->isVisible($document, $tenantId, $ownerId);
    }

    public function isVisible(KnowledgeDocument $document, ?string $tenantId, ?string $ownerId = null): bool
    {
        return match ($document->visibility()) {
            VisibilityPolicy::PublicGlobal => true,
            VisibilityPolicy::TenantOnly => $document->tenantId() === null || $document->tenantId() === $tenantId,
            VisibilityPolicy::DepartmentOnly => $document->tenantId() === $tenantId,
            VisibilityPolicy::PrivateOwner => $ownerId !== null && $document->owner() === $ownerId && $document->tenantId() === $tenantId,
            VisibilityPolicy::SharedSelected => $document->tenantId() === null || $document->tenantId() === $tenantId,
        };
    }

    public function canPublish(KnowledgeDocument $document): bool
    {
        return in_array($document->status(), [
            KnowledgeLifecycleStatus::Approved,
            KnowledgeLifecycleStatus::Review,
        ], true) || $document->status() === KnowledgeLifecycleStatus::Approved;
    }

    public function allowsPublishFrom(KnowledgeDocument $document): bool
    {
        return $document->status()->canTransitionTo(KnowledgeLifecycleStatus::Published)
            || $document->status() === KnowledgeLifecycleStatus::Approved;
    }

    public function enforcesTenantIsolation(): bool
    {
        return true;
    }

    public function allowsCrossTenantLeak(): bool
    {
        return false;
    }

    public function collectionVisible(KnowledgeCollection $collection, ?string $tenantId): bool
    {
        return $collection->isVisibleToTenant($tenantId);
    }

    /**
     * @param  list<KnowledgeDocument>  $documents
     * @return list<KnowledgeDocument>
     */
    public function filterRetrievable(array $documents, ?string $tenantId, ?string $ownerId = null): array
    {
        return array_values(array_filter(
            $documents,
            fn (KnowledgeDocument $d): bool => $this->canRetrieve($d, $tenantId, $ownerId)
        ));
    }
}
