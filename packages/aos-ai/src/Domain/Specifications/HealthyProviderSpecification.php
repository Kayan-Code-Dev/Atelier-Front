<?php

declare(strict_types=1);

namespace DressnMore\Aos\Ai\Domain\Specifications;

use DressnMore\Aos\Ai\Domain\Health\HealthStatus;
use DressnMore\Aos\Ai\Domain\Provider\ProviderDescriptor;

final class HealthyProviderSpecification
{
    public function isSatisfiedBy(ProviderDescriptor $provider): bool
    {
        return $provider->isEnabled() && $provider->health()->isUsable();
    }
}
