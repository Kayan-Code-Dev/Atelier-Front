<?php

declare(strict_types=1);

namespace DressnMore\CustomerBinding\Application;

use DressnMore\CustomerBinding\Contracts\CustomerToolAdapterInterface;
use DressnMore\CustomerBinding\Domain\Tools\CustomerToolCatalog;
use DressnMore\CustomerBinding\Domain\Tools\CustomerToolName;

/**
 * Contract-only adapter: exposes Customer tool contracts to AOS Tool Gateway.
 * Does not execute DressnMore domain writes/reads.
 */
final class CustomerToolAdapter implements CustomerToolAdapterInterface
{
    public function contracts(): array
    {
        return CustomerToolCatalog::all();
    }

    public function supports(string $toolName): bool
    {
        foreach (CustomerToolName::cases() as $case) {
            if ($case->value === $toolName) {
                return true;
            }
        }

        return false;
    }
}
