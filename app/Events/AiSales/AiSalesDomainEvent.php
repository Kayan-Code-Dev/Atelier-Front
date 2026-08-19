<?php

declare(strict_types=1);

namespace App\Events\AiSales;

use App\Support\AiSales\AiSalesEventType;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Platform sales domain event — published on Laravel's bus (existing app event architecture).
 */
final class AiSalesDomainEvent
{
    use Dispatchable;
    use SerializesModels;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public readonly AiSalesEventType $type,
        public readonly array $payload = [],
    ) {}
}
