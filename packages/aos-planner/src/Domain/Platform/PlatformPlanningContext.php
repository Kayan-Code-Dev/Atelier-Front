<?php

declare(strict_types=1);

namespace DressnMore\Aos\Planner\Domain\Platform;

/**
 * Sprint 18 planning context — tenant-aware, metadata only.
 */
final class PlatformPlanningContext
{
    /**
     * @param list<string> $permissions
     * @param list<string> $grantedCapabilities
     * @param list<string> $grantedTools
     * @param list<string> $availableTools
     * @param list<string> $availableCapabilities
     */
    public function __construct(
        private readonly string $message,
        private readonly string $tenantId,
        private readonly ?string $conversationId = null,
        private readonly ?string $userId = null,
        private readonly ?string $branchId = null,
        private readonly string $language = 'ar',
        private readonly string $subscriptionPlan = 'basic',
        private readonly array $permissions = [],
        private readonly array $grantedCapabilities = [],
        private readonly array $grantedTools = [],
        private readonly array $availableTools = [],
        private readonly array $availableCapabilities = [],
        private readonly string $correlationId = '',
    ) {}

    public function message(): string { return $this->message; }
    public function tenantId(): string { return $this->tenantId; }
    public function conversationId(): ?string { return $this->conversationId; }
    public function userId(): ?string { return $this->userId; }
    public function branchId(): ?string { return $this->branchId; }
    public function language(): string { return $this->language; }
    public function subscriptionPlan(): string { return $this->subscriptionPlan; }
    /** @return list<string> */
    public function permissions(): array { return $this->permissions; }
    /** @return list<string> */
    public function grantedCapabilities(): array { return $this->grantedCapabilities; }
    /** @return list<string> */
    public function grantedTools(): array { return $this->grantedTools; }
    /** @return list<string> */
    public function availableTools(): array { return $this->availableTools; }
    /** @return list<string> */
    public function availableCapabilities(): array { return $this->availableCapabilities; }
    public function correlationId(): string
    {
        return $this->correlationId !== '' ? $this->correlationId : 'corr_unknown';
    }
}
