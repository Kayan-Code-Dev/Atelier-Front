<?php

declare(strict_types=1);

namespace DressnMore\Aos\Communication\Domain\Repository;

use DressnMore\Aos\Communication\Domain\Delivery\DeliveryRecord;
use DressnMore\Aos\Communication\Domain\Message\MessageId;

interface DeliveryRepositoryInterface
{
    public function save(DeliveryRecord $record): void;
    public function findByMessageId(MessageId $messageId): ?DeliveryRecord;
}
