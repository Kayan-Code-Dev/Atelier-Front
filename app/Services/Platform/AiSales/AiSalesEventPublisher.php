<?php

declare(strict_types=1);

namespace App\Services\Platform\AiSales;

use App\Events\AiSales\AiSalesDomainEvent;
use App\Models\Central\CrmLead;
use App\Models\Central\CrmLeadEvent;
use App\Support\AiSales\AiSalesEventType;
use DressnMore\Aos\Events\Contracts\EventBusInterface;
use Illuminate\Contracts\Events\Dispatcher;

final class AiSalesEventPublisher
{
    public function __construct(
        private readonly Dispatcher $dispatcher,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function publish(AiSalesEventType $type, array $payload = [], ?CrmLead $lead = null, ?int $actorId = null): void
    {
        $event = new AiSalesDomainEvent($type, $payload);
        if (app()->bound(EventBusInterface::class)) {
            app(EventBusInterface::class)->publish($event);
        } else {
            $this->dispatcher->dispatch($event);
        }

        if ($lead !== null) {
            CrmLeadEvent::query()->create([
                'lead_id' => $lead->id,
                'type' => $type->value,
                'title' => $type->value,
                'body' => is_string($payload['body'] ?? null) ? $payload['body'] : ($payload['reason'] ?? null),
                'meta' => $payload,
                'created_by' => $actorId,
            ]);
        }
    }
}
