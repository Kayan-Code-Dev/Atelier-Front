<?php

declare(strict_types=1);

namespace DressnMore\CustomerBinding\Application;

use DressnMore\CustomerBinding\Contracts\CustomerEventPublisherInterface;
use DressnMore\CustomerBinding\Contracts\CustomerTimelineBuilderInterface;
use DressnMore\CustomerBinding\Domain\Customer\CustomerReadModel;
use DressnMore\CustomerBinding\Domain\Events\CustomerDomainEvent;
use DressnMore\CustomerBinding\Domain\Timeline\CustomerTimeline;
use DressnMore\CustomerBinding\Domain\Timeline\TimelineEntry;
use DressnMore\CustomerBinding\Domain\Timeline\TimelineSource;

final class CustomerTimelineBuilder implements CustomerTimelineBuilderInterface
{
    public function __construct(private readonly ?CustomerEventPublisherInterface $events = null) {}

    public function build(CustomerReadModel $customer): CustomerTimeline
    {
        $entries = [];

        foreach ($customer->timelineSeed() as $row) {
            $source = TimelineSource::tryFrom((string) ($row['source'] ?? '')) ?? TimelineSource::AiConversations;
            $entries[] = new TimelineEntry(
                $source,
                (string) ($row['at'] ?? 'unknown'),
                (string) ($row['title'] ?? 'Event'),
                isset($row['detail']) ? (string) $row['detail'] : null,
            );
        }

        foreach ($customer->reservations() as $reservation) {
            $entries[] = new TimelineEntry(
                TimelineSource::Reservations,
                (string) ($reservation['at'] ?? 'unknown'),
                'Reservation '.(string) ($reservation['id'] ?? ''),
                isset($reservation['status']) ? (string) $reservation['status'] : null,
            );
        }

        foreach ($customer->invoices() as $invoice) {
            $entries[] = new TimelineEntry(
                TimelineSource::Invoices,
                (string) ($invoice['at'] ?? 'unknown'),
                'Invoice '.(string) ($invoice['id'] ?? ''),
                isset($invoice['status']) ? (string) $invoice['status'] : null,
            );
        }

        foreach ($customer->orders() as $order) {
            $entries[] = new TimelineEntry(
                TimelineSource::Orders,
                (string) ($order['at'] ?? 'unknown'),
                'Order '.(string) ($order['id'] ?? ''),
                isset($order['status']) ? (string) $order['status'] : null,
            );
        }

        $timeline = new CustomerTimeline($customer->id()->toString(), $customer->tenantId(), $entries);

        $this->events?->publish(CustomerDomainEvent::customerTimelineBuilt([
            'customerId' => $customer->id()->toString(),
            'entries' => count($entries),
        ]));

        return $timeline;
    }
}
