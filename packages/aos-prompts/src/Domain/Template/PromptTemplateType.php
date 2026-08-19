<?php

declare(strict_types=1);

namespace DressnMore\Aos\Prompts\Domain\Template;

enum PromptTemplateType: string
{
    case Greeting = 'greeting';
    case Sales = 'sales';
    case Support = 'support';
    case Complaint = 'complaint';
    case Reservation = 'reservation';
    case Quotation = 'quotation';
    case Invoice = 'invoice';
    case FollowUp = 'follow_up';
    case Reminder = 'reminder';
    case Escalation = 'escalation';
    case GeneralConversation = 'general_conversation';
    case UnknownIntent = 'unknown_intent';
}
