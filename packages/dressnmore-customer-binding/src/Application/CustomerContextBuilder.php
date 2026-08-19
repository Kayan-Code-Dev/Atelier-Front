<?php

declare(strict_types=1);

namespace DressnMore\CustomerBinding\Application;

use DressnMore\CustomerBinding\Contracts\CustomerContextBuilderInterface;
use DressnMore\CustomerBinding\Contracts\CustomerEventPublisherInterface;
use DressnMore\CustomerBinding\Domain\Context\CustomerContext;
use DressnMore\CustomerBinding\Domain\Customer\CustomerReadModel;
use DressnMore\CustomerBinding\Domain\Events\CustomerDomainEvent;

final class CustomerContextBuilder implements CustomerContextBuilderInterface
{
    public function __construct(private readonly ?CustomerEventPublisherInterface $events = null) {}

    public function build(CustomerReadModel $customer): CustomerContext
    {
        $context = new CustomerContext(
            $customer->id(),
            $customer->tenantId(),
            [
                'name' => $customer->displayName(),
                'phone' => $customer->phone(),
            ],
            $customer->measurements(),
            $customer->orders(),
            $customer->reservations(),
            $customer->invoices(),
            $customer->paymentStatus(),
            $customer->preferredLanguage(),
            $customer->preferences(),
            $customer->vip(),
            $customer->tags(),
            $customer->notes(),
            $customer->lastInteractionAt(),
            $customer->aiSummaryPlaceholder(),
        );

        $this->events?->publish(CustomerDomainEvent::customerContextBuilt([
            'customerId' => $customer->id()->toString(),
            'tenantId' => $customer->tenantId(),
        ]));

        return $context;
    }
}
