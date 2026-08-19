<?php

declare(strict_types=1);

namespace DressnMore\Aos\Communication\Domain\Delivery;

enum DeliveryStatus: string
{
    case Queued = 'queued';
    case Sent = 'sent';
    case Delivered = 'delivered';
    case Read = 'read';
    case Failed = 'failed';
    case Expired = 'expired';
}
