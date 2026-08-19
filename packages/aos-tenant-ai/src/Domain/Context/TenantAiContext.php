<?php

declare(strict_types=1);

namespace DressnMore\Aos\TenantAi\Domain\Context;

/**
 * Metadata-only tenant context for Planner/Prompt — never loads business entities.
 */
final class TenantAiContext
{
    /**
     * @param list<string> $permissions
     * @param list<string> $allowedCapabilities
     */
    public function __construct(
        private readonly string $tenantId,
        private readonly ?string $branchId,
        private readonly ?string $userId,
        private readonly ?string $role,
        private readonly array $permissions,
        private readonly string $subscriptionPlan,
        private readonly string $language,
        private readonly string $currency,
        private readonly ?string $country,
        private readonly string $timezone,
        private readonly array $allowedCapabilities = [],
    ) {}

    public function tenantId(): string { return $this->tenantId; }
    public function branchId(): ?string { return $this->branchId; }
    public function userId(): ?string { return $this->userId; }
    public function role(): ?string { return $this->role; }
    /** @return list<string> */
    public function permissions(): array { return $this->permissions; }
    public function subscriptionPlan(): string { return $this->subscriptionPlan; }
    public function language(): string { return $this->language; }
    public function currency(): string { return $this->currency; }
    public function country(): ?string { return $this->country; }
    public function timezone(): string { return $this->timezone; }
    /** @return list<string> */
    public function allowedCapabilities(): array { return $this->allowedCapabilities; }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'tenantId' => $this->tenantId,
            'branchId' => $this->branchId,
            'userId' => $this->userId,
            'role' => $this->role,
            'permissions' => $this->permissions,
            'subscriptionPlan' => $this->subscriptionPlan,
            'language' => $this->language,
            'currency' => $this->currency,
            'country' => $this->country,
            'timezone' => $this->timezone,
            'allowedCapabilities' => $this->allowedCapabilities,
        ];
    }
}
