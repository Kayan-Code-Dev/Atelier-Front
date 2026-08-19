<?php

declare(strict_types=1);

namespace DressnMore\CustomerBinding\Contracts;

interface CustomerCapabilityProviderInterface
{
    /**
     * @return list<string>
     */
    public function capabilities(): array;

    public function supports(string $capability): bool;
}
