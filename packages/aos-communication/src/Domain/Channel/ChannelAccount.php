<?php

declare(strict_types=1);

namespace DressnMore\Aos\Communication\Domain\Channel;

enum VerificationStatus: string { case Pending='pending'; case Verified='verified'; case Rejected='rejected'; }
enum WebhookStatus: string { case NotConfigured='not_configured'; case Active='active'; case Error='error'; }
enum ChannelHealthStatus: string { case Healthy='healthy'; case Degraded='degraded'; case Unhealthy='unhealthy'; }

final class ChannelAccount
{
    /**
     * @param array<string, scalar|null> $metadata
     */
    public function __construct(
        private readonly ChannelType $channelType,
        private readonly string $externalIdentifier,
        private readonly VerificationStatus $verificationStatus = VerificationStatus::Pending,
        private readonly ?string $accessTokenPlaceholder = null,
        private readonly WebhookStatus $webhookStatus = WebhookStatus::NotConfigured,
        private readonly ChannelHealthStatus $healthStatus = ChannelHealthStatus::Healthy,
        private readonly ?string $tenantId = null,
        private readonly array $metadata = [],
    ) {}

    public function channelType(): ChannelType { return $this->channelType; }
    public function externalIdentifier(): string { return $this->externalIdentifier; }
    public function verificationStatus(): VerificationStatus { return $this->verificationStatus; }
    public function accessTokenPlaceholder(): ?string { return $this->accessTokenPlaceholder; }
    public function webhookStatus(): WebhookStatus { return $this->webhookStatus; }
    public function healthStatus(): ChannelHealthStatus { return $this->healthStatus; }
    public function tenantId(): ?string { return $this->tenantId; }
    /** @return array<string, scalar|null> */
    public function metadata(): array { return $this->metadata; }
}
