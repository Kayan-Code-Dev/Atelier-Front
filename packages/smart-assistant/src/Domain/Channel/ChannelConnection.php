<?php

declare(strict_types=1);

namespace DressnMore\SmartAssistant\Domain\Channel;

final class ChannelConnection
{
    /**
     * @param array<string, mixed> $credentialsRef opaque reference only — never raw secrets in domain
     */
    public function __construct(
        private readonly string $id,
        private readonly string $tenantId,
        private readonly string $channelId,
        private readonly string $status = 'disconnected',
        private readonly array $credentialsRef = [],
    ) {}

    public function id(): string { return $this->id; }
    public function tenantId(): string { return $this->tenantId; }
    public function channelId(): string { return $this->channelId; }
    public function status(): string { return $this->status; }
    /** @return array<string, mixed> */
    public function credentialsRef(): array { return $this->credentialsRef; }
}
