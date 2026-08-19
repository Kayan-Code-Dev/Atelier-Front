<?php

declare(strict_types=1);

namespace DressnMore\CustomerBinding\Application;

use DressnMore\CustomerBinding\Contracts\CustomerCapabilityProviderInterface;
use DressnMore\CustomerBinding\Domain\Tools\CustomerToolCatalog;

final class CustomerCapabilityProvider implements CustomerCapabilityProviderInterface
{
    public function capabilities(): array
    {
        $caps = [];
        foreach (CustomerToolCatalog::all() as $contract) {
            $caps[] = $contract->requiredCapability();
        }

        return array_values(array_unique($caps));
    }

    public function supports(string $capability): bool
    {
        return in_array($capability, $this->capabilities(), true);
    }
}
