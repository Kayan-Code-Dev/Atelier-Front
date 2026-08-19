<?php

declare(strict_types=1);

namespace DressnMore\CustomerBinding\Application;

use DressnMore\CustomerBinding\Contracts\CustomerEventPublisherInterface;
use DressnMore\CustomerBinding\Contracts\CustomerSnapshotBuilderInterface;
use DressnMore\CustomerBinding\Domain\Customer\CustomerReadModel;
use DressnMore\CustomerBinding\Domain\Events\CustomerDomainEvent;
use DressnMore\CustomerBinding\Domain\Snapshot\CustomerSnapshot;

final class CustomerSnapshotBuilder implements CustomerSnapshotBuilderInterface
{
    public function __construct(private readonly ?CustomerEventPublisherInterface $events = null) {}

    public function build(CustomerReadModel $customer): CustomerSnapshot
    {
        $snapshot = new CustomerSnapshot(
            $customer->id(),
            $customer->tenantId(),
            $customer->displayName(),
            $customer->vip(),
            $customer->preferredLanguage(),
            $customer->paymentStatus(),
            $customer->tags(),
            count($customer->orders()),
            count($customer->reservations()),
            count($customer->invoices()),
            $customer->lastInteractionAt(),
            $customer->aiSummaryPlaceholder() ?? ('Customer '.$customer->displayName()),
            [
                'phone' => $customer->phone(),
                'notesCount' => count($customer->notes()),
            ],
        );

        $this->events?->publish(CustomerDomainEvent::customerSnapshotBuilt([
            'customerId' => $customer->id()->toString(),
            'tenantId' => $customer->tenantId(),
        ]));
        $this->events?->publish(CustomerDomainEvent::customerSummaryBuilt([
            'customerId' => $customer->id()->toString(),
        ]));

        return $snapshot;
    }
}
