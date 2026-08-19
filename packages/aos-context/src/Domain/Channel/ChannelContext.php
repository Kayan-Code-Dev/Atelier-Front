<?php

declare(strict_types=1);

namespace DressnMore\Aos\Context\Domain\Channel;

use DressnMore\Aos\Context\Domain\Identity\ChannelIdentityId;
use DressnMore\Aos\Context\Domain\Identity\ChannelType;
use DressnMore\Aos\Context\Domain\Identity\ExternalIdentifier;
use DressnMore\Aos\Context\Domain\Identity\VerificationStatus;

/**
 * Channel slice contributed to the Context Snapshot.
 */
final class ChannelContext
{
    public function __construct(
        private readonly ChannelType $channelType,
        private readonly ExternalIdentifier $externalIdentifier,
        private readonly string $channelAccount,
        private readonly ?ChannelIdentityId $channelIdentityId = null,
        private readonly VerificationStatus $verificationStatus = VerificationStatus::Unverified,
    ) {}

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

    public function channelIdentityId(): ?ChannelIdentityId
    {
        return $this->channelIdentityId;
    }

    public function verificationStatus(): VerificationStatus
    {
        return $this->verificationStatus;
    }
}
