<?php

declare(strict_types=1);

namespace DressnMore\Aos\Knowledge\Domain\Collection;

/**
 * Knowledge collection aggregate root (in-memory).
 */
final class KnowledgeCollection
{
    public function __construct(
        private readonly CollectionId $id,
        private readonly string $name,
        private readonly CollectionScope $scope,
        private readonly ?string $tenantId = null,
        private readonly ?string $departmentId = null,
        private readonly string $owner = 'system',
        private readonly string $description = '',
    ) {
        if ($scope === CollectionScope::Global && $tenantId !== null) {
            throw new \InvalidArgumentException('Global collections cannot have a tenantId.');
        }
        if (in_array($scope, [CollectionScope::Tenant, CollectionScope::Department, CollectionScope::Private, CollectionScope::Shared], true)
            && ($tenantId === null || $tenantId === '')) {
            throw new \InvalidArgumentException('Tenant-scoped collections require tenantId.');
        }
    }

    public function id(): CollectionId
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function scope(): CollectionScope
    {
        return $this->scope;
    }

    public function tenantId(): ?string
    {
        return $this->tenantId;
    }

    public function departmentId(): ?string
    {
        return $this->departmentId;
    }

    public function owner(): string
    {
        return $this->owner;
    }

    public function description(): string
    {
        return $this->description;
    }

    public function isVisibleToTenant(?string $tenantId): bool
    {
        return match ($this->scope) {
            CollectionScope::Global => true,
            CollectionScope::Tenant, CollectionScope::Department, CollectionScope::Private, CollectionScope::Shared => $this->tenantId === $tenantId,
        };
    }
}
