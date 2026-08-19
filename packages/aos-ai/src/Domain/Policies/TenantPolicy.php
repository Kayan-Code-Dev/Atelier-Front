<?php

declare(strict_types=1);

namespace DressnMore\Aos\Ai\Domain\Policies;

use DressnMore\Aos\Ai\Domain\Provider\ProviderDescriptor;
use DressnMore\Aos\Ai\Domain\Request\AiRequest;

final class TenantPolicy
{
    /**
     * @param  array<string, list<string>>  $tenantAllowList  tenantId => provider ids
     */
    public function __construct(
        private readonly array $tenantAllowList = [],
    ) {}

    public function allows(ProviderDescriptor $provider, AiRequest $request): bool
    {
        $tenantId = $request->tenantId();
        if ($tenantId === null || $this->tenantAllowList === []) {
            return true;
        }

        $allowed = $this->tenantAllowList[$tenantId] ?? null;
        if ($allowed === null) {
            return true;
        }

        return in_array($provider->id()->toString(), $allowed, true);
    }
}
