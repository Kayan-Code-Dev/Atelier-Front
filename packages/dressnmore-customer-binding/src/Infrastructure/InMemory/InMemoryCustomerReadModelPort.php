<?php

declare(strict_types=1);

namespace DressnMore\CustomerBinding\Infrastructure\InMemory;

use DressnMore\CustomerBinding\Contracts\CustomerReadModelPortInterface;
use DressnMore\CustomerBinding\Domain\Customer\CustomerId;
use DressnMore\CustomerBinding\Domain\Customer\CustomerReadModel;

/**
 * Test/demo read-model port only — not a DressnMore repository.
 */
final class InMemoryCustomerReadModelPort implements CustomerReadModelPortInterface
{
    /** @var array<string, CustomerReadModel> */
    private array $byKey = [];

    public function seed(CustomerReadModel $customer): void
    {
        $this->byKey[$customer->tenantId().':'.$customer->id()->toString()] = $customer;
        if ($customer->phone() !== null) {
            $this->byKey[$customer->tenantId().':phone:'.$customer->phone()] = $customer;
        }
    }

    public function findById(string $tenantId, CustomerId $customerId): ?CustomerReadModel
    {
        return $this->byKey[$tenantId.':'.$customerId->toString()] ?? null;
    }

    public function findByPhone(string $tenantId, string $phone): ?CustomerReadModel
    {
        return $this->byKey[$tenantId.':phone:'.$phone] ?? null;
    }

    public function search(string $tenantId, string $query, int $limit = 10): array
    {
        $out = [];
        foreach ($this->byKey as $key => $customer) {
            if (! str_starts_with($key, $tenantId.':') || str_contains($key, ':phone:')) {
                continue;
            }
            if (str_contains(mb_strtolower($customer->displayName()), mb_strtolower($query))) {
                $out[] = $customer;
            }
            if (count($out) >= $limit) {
                break;
            }
        }

        return $out;
    }
}
