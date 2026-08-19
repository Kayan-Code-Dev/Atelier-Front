<?php

declare(strict_types=1);

namespace DressnMore\Aos\TenantAi\Domain\Integration;

final class TenantIntegrationBinding
{
    /**
     * @param array<string, scalar|null> $metadata
     */
    public function __construct(
        private readonly string $bindingId,
        private readonly string $tenantId,
        private readonly IntegrationChannel $channel,
        private readonly bool $enabled = false,
        private readonly array $metadata = [],
    ) {}

    public function bindingId(): string { return $this->bindingId; }
    public function tenantId(): string { return $this->tenantId; }
    public function channel(): IntegrationChannel { return $this->channel; }
    public function enabled(): bool { return $this->enabled; }
    /** @return array<string, scalar|null> */
    public function metadata(): array { return $this->metadata; }
}
