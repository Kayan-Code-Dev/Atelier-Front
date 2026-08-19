<?php

declare(strict_types=1);

namespace DressnMore\Aos\Conversation\Domain\Message;

enum MessageDirection: string
{
    case Inbound = 'inbound';
    case Outbound = 'outbound';
    case Internal = 'internal';
}
