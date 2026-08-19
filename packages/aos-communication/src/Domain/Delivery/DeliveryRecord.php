<?php

declare(strict_types=1);

namespace DressnMore\Aos\Communication\Domain\Delivery;

use DateTimeImmutable;
use DressnMore\Aos\Communication\Domain\Message\MessageId;

final class DeliveryRecord
{
    public function __construct(
        private readonly MessageId $messageId,
        private readonly DeliveryStatus $status,
        private readonly DateTimeImmutable $updatedAt = new DateTimeImmutable(),
        private readonly ?string $reason = null,
    ) {}

    public function messageId(): MessageId { return $this->messageId; }
    public function status(): DeliveryStatus { return $this->status; }
    public function updatedAt(): DateTimeImmutable { return $this->updatedAt; }
    public function reason(): ?string { return $this->reason; }
}
