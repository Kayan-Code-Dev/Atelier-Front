<?php

declare(strict_types=1);

namespace DressnMore\Aos\Conversation\Domain\Message;

enum MessageAuthorKind: string
{
    case Customer = 'customer';
    case AIAgent = 'ai_agent';
    case HumanStaff = 'human_staff';
    case System = 'system';
}
