<?php

declare(strict_types=1);

namespace DressnMore\Aos\TenantAi\Domain\Workspace;

final class AiWorkspace
{
    /**
     * @param array<string, scalar|null> $settings
     */
    public function __construct(
        private readonly string $workspaceId,
        private readonly string $tenantId,
        private readonly string $language = 'ar',
        private readonly string $timezone = 'Asia/Riyadh',
        private readonly string $currency = 'SAR',
        private readonly string $subscriptionPlan = 'basic',
        private readonly bool $aiEnabled = true,
        private readonly array $settings = [],
    ) {
        if ($tenantId === '' || $workspaceId === '') {
            throw new \InvalidArgumentException('workspaceId and tenantId are required.');
        }
    }

    public function workspaceId(): string { return $this->workspaceId; }
    public function tenantId(): string { return $this->tenantId; }
    public function language(): string { return $this->language; }
    public function timezone(): string { return $this->timezone; }
    public function currency(): string { return $this->currency; }
    public function subscriptionPlan(): string { return $this->subscriptionPlan; }
    public function aiEnabled(): bool { return $this->aiEnabled; }
    /** @return array<string, scalar|null> */
    public function settings(): array { return $this->settings; }

    /**
     * @param array<string, scalar|null> $settings
     */
    public function withSettings(array $settings, ?string $language = null, ?string $timezone = null, ?string $currency = null, ?string $subscriptionPlan = null, ?bool $aiEnabled = null): self
    {
        return new self(
            $this->workspaceId,
            $this->tenantId,
            $language ?? $this->language,
            $timezone ?? $this->timezone,
            $currency ?? $this->currency,
            $subscriptionPlan ?? $this->subscriptionPlan,
            $aiEnabled ?? $this->aiEnabled,
            $settings,
        );
    }
}
