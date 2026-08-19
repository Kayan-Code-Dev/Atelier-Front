<?php

declare(strict_types=1);

namespace DressnMore\CustomerBinding\Domain\Timeline;

enum TimelineSource: string
{
    case WhatsApp = 'whatsapp';
    case Messenger = 'messenger';
    case Instagram = 'instagram';
    case Comments = 'comments';
    case Reservations = 'reservations';
    case Invoices = 'invoices';
    case Orders = 'orders';
    case Approvals = 'approvals';
    case AiConversations = 'ai_conversations';
    case HumanConversations = 'human_conversations';
}
