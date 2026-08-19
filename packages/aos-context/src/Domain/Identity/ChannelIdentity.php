<?php

declare(strict_types=1);

namespace DressnMore\Aos\Context\Domain\Identity;

use DressnMore\Aos\Context\Domain\Tenant\TenantId;

/**
 * Channel-side identity binding (one external account on one channel).
 */
final class ChannelIdentity
{
    public function __construct(
        private readonly ChannelIdentityId $id,
        private readonly ChannelType $channelType,
        private readonly ExternalIdentifier $externalIdentifier,
        private readonly string $channelAccount,
        private VerificationStatus $verificationStatus = VerificationStatus::Unverified,
        private ?CustomerId $linkedCustomerId = null,
        private ?TenantId $linkedTenantId = null,
    ) {}

    public static function create(
        ChannelType $channelType,
        ExternalIdentifier $externalIdentifier,
        string $channelAccount,
        ?TenantId $linkedTenantId = null,
    ): self {
        return new self(
            ChannelIdentityId::generate(),
            $channelType,
            $externalIdentifier,
            $channelAccount,
            VerificationStatus::Unverified,
            null,
            $linkedTenantId,
        );
    }

    public function id(): ChannelIdentityId
    {
        return $this->id;
    }

    public function channelType(): ChannelType
    {
        return $this->channelType;
    }

    public function externalIdentifier(): ExternalIdentifier
    {
        return $this->externalIdentifier;
    }

    public function channelAccount(): string
    {
        return $this->channelAccount;
    }

    public function verificationStatus(): VerificationStatus
    {
        return $this->verificationStatus;
    }

    public function linkedCustomerId(): ?CustomerId
    {
        return $this->linkedCustomerId;
    }

    public function linkedTenantId(): ?TenantId
    {
        return $this->linkedTenantId;
    }

    public function bindTenant(TenantId $tenantId): void
    {
        $this->linkedTenantId = $tenantId;
    }

    public function linkCustomer(CustomerId $customerId, VerificationStatus $status = VerificationStatus::Verified): void
    {
        $this->linkedCustomerId = $customerId;
        $this->verificationStatus = $status;
    }

    public function markPendingHuman(): void
    {
        $this->verificationStatus = VerificationStatus::PendingHuman;
    }

    public function markConflict(): void
    {
        $this->verificationStatus = VerificationStatus::Conflict;
    }

    public function markVerified(): void
    {
        $this->verificationStatus = VerificationStatus::Verified;
    }
}
