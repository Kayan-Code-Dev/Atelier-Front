<?php

declare(strict_types=1);

namespace DressnMore\CustomerBinding\Contracts;

use DressnMore\CustomerBinding\Domain\Customer\CustomerId;
use DressnMore\CustomerBinding\Domain\Customer\CustomerReadModel;

interface CustomerResolverInterface
{
    public function resolveById(string $tenantId, CustomerId $customerId): ?CustomerReadModel;

    public function resolveByPhone(string $tenantId, string $phone): ?CustomerReadModel;

    public function exists(string $tenantId, string $phone): bool;
}
