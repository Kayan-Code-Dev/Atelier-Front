<?php

declare(strict_types=1);

namespace DressnMore\Aos\Context\Domain\Identity;

use DressnMore\Aos\Context\Domain\Tenant\TenantId;

/**
 * Unified customer identity that may link multiple channel identities.
 * Never assumes identity equality without confidence / verification.
 */
final class CustomerIdentity
{
    /** @var list<ChannelIdentityId> */
    private array $linkedChannelIds = [];

    public function __construct(
        private readonly CustomerId $id,
        private readonly TenantId $tenantId,
        private readonly ?string $displayName = null,
    ) {}

    public static function create(TenantId $tenantId, ?string $displayName = null): self
    {
        return new self(CustomerId::generate(), $tenantId, $displayName);
    }

    public function id(): CustomerId
    {
        return $this->id;
    }

    public function tenantId(): TenantId
    {
        return $this->tenantId;
    }

    public function displayName(): ?string
    {
        return $this->displayName;
    }

    /**
     * @return list<ChannelIdentityId>
     */
    public function linkedChannelIds(): array
    {
        return $this->linkedChannelIds;
    }

    public function linkChannel(ChannelIdentityId $channelIdentityId): void
    {
        foreach ($this->linkedChannelIds as $existing) {
            if ($existing->equals($channelIdentityId)) {
                return;
            }
        }
        $this->linkedChannelIds[] = $channelIdentityId;
    }

    public function isLinkedTo(ChannelIdentityId $channelIdentityId): bool
    {
        foreach ($this->linkedChannelIds as $existing) {
            if ($existing->equals($channelIdentityId)) {
                return true;
            }
        }

        return false;
    }
}
