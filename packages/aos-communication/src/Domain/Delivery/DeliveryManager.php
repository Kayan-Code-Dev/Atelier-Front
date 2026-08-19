<?php

declare(strict_types=1);

namespace DressnMore\Aos\Communication\Domain\Delivery;

use DressnMore\Aos\Communication\Domain\Message\MessageId;

final class DeliveryManager
{
    /** @var array<string, DeliveryRecord> */
    private array $records = [];

    public function track(MessageId $messageId, DeliveryStatus $status, ?string $reason = null): DeliveryRecord
    {
        $record = new DeliveryRecord($messageId, $status, reason: $reason);
        $this->records[$messageId->toString()] = $record;

        return $record;
    }

    public function get(MessageId $messageId): ?DeliveryRecord
    {
        return $this->records[$messageId->toString()] ?? null;
    }
}
