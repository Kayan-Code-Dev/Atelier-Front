<?php

declare(strict_types=1);

namespace DressnMore\CustomerBinding\Application;

use DressnMore\CustomerBinding\Contracts\CustomerEventPublisherInterface;
use DressnMore\CustomerBinding\Contracts\CustomerReadModelPortInterface;
use DressnMore\CustomerBinding\Contracts\CustomerResolverInterface;
use DressnMore\CustomerBinding\Domain\Customer\CustomerId;
use DressnMore\CustomerBinding\Domain\Customer\CustomerReadModel;
use DressnMore\CustomerBinding\Domain\Events\CustomerDomainEvent;

final class CustomerResolver implements CustomerResolverInterface
{
    public function __construct(
        private readonly CustomerReadModelPortInterface $readModelPort,
        private readonly ?CustomerEventPublisherInterface $events = null,
    ) {}

    public function resolveById(string $tenantId, CustomerId $customerId): ?CustomerReadModel
    {
        $customer = $this->readModelPort->findById($tenantId, $customerId);
        if ($customer !== null) {
            $this->events?->publish(CustomerDomainEvent::customerResolved([
                'customerId' => $customerId->toString(),
                'tenantId' => $tenantId,
            ]));
        }

        return $customer;
    }

    public function resolveByPhone(string $tenantId, string $phone): ?CustomerReadModel
    {
        $customer = $this->readModelPort->findByPhone($tenantId, $phone);
        if ($customer !== null) {
            $this->events?->publish(CustomerDomainEvent::customerResolved([
                'customerId' => $customer->id()->toString(),
                'tenantId' => $tenantId,
                'phone' => $phone,
            ]));
        }

        return $customer;
    }

    public function exists(string $tenantId, string $phone): bool
    {
        return $this->readModelPort->findByPhone($tenantId, $phone) !== null;
    }
}
