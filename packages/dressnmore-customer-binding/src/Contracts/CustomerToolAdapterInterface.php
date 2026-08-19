<?php

declare(strict_types=1);

namespace DressnMore\CustomerBinding\Contracts;

use DressnMore\CustomerBinding\Domain\Tools\CustomerToolContract;

/**
 * Port for AOS Tool Gateway registration — no DressnMore domain execution here.
 */
interface CustomerToolAdapterInterface
{
    /**
     * @return list<CustomerToolContract>
     */
    public function contracts(): array;

    public function supports(string $toolName): bool;
}
