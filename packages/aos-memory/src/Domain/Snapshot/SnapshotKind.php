<?php

declare(strict_types=1);

namespace DressnMore\Aos\Memory\Domain\Snapshot;

enum SnapshotKind: string
{
    case Conversation = 'conversation';
    case Customer = 'customer';
    case Business = 'business';
}
