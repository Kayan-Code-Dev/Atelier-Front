<?php

declare(strict_types=1);

namespace DressnMore\Aos\Communication\Domain\Delivery;

use DressnMore\Aos\Communication\Domain\Message\MessageId;

final class ReadReceiptManager
{
    /** @var array<string, bool> */
    private array $read = [];

    public function markRead(MessageId $messageId): void
    {
        $this->read[$messageId->toString()] = true;
    }

    public function isRead(MessageId $messageId): bool
    {
        return $this->read[$messageId->toString()] ?? false;
    }
}
