<?php

declare(strict_types=1);

namespace DressnMore\CustomerBinding\Contracts;

use DressnMore\CustomerBinding\Domain\Customer\CustomerId;
use DressnMore\CustomerBinding\Domain\Customer\CustomerReadModel;

interface CustomerReadModelPortInterface
{
    public function findById(string $tenantId, CustomerId $customerId): ?CustomerReadModel;

    public function findByPhone(string $tenantId, string $phone): ?CustomerReadModel;

    /**
     * @return list<CustomerReadModel>
     */
    public function search(string $tenantId, string $query, int $limit = 10): array;
}
