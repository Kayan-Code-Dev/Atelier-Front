<?php

declare(strict_types=1);

namespace DressnMore\Aos\TenantAi\Domain\Subscription;

final class SubscriptionEntitlement
{
    /**
     * @param list<string> $allowedDomains
     * @param list<string> $allowedCapabilities
     * @param list<string> $allowedTools
     */
    public function __construct(
        private readonly SubscriptionPlan $plan,
        private readonly array $allowedDomains,
        private readonly array $allowedCapabilities,
        private readonly array $allowedTools,
    ) {}

    public function plan(): SubscriptionPlan { return $this->plan; }
    /** @return list<string> */
    public function allowedDomains(): array { return $this->allowedDomains; }
    /** @return list<string> */
    public function allowedCapabilities(): array { return $this->allowedCapabilities; }
    /** @return list<string> */
    public function allowedTools(): array { return $this->allowedTools; }

    public function allowsTool(string $toolName): bool
    {
        if ($this->plan === SubscriptionPlan::Enterprise) {
            return true;
        }

        return in_array($toolName, $this->allowedTools, true);
    }

    public function allowsCapability(string $capability): bool
    {
        if ($this->plan === SubscriptionPlan::Enterprise) {
            return true;
        }

        return in_array($capability, $this->allowedCapabilities, true);
    }
}
