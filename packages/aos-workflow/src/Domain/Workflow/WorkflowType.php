<?php

declare(strict_types=1);

namespace DressnMore\Aos\Workflow\Domain\Workflow;

enum WorkflowType: string
{
    case Conversation = 'conversation_workflow';
    case Sales = 'sales_workflow';
    case Marketing = 'marketing_workflow';
    case Support = 'support_workflow';
    case Reservation = 'reservation_workflow';
    case Invoice = 'invoice_workflow';
    case Reminder = 'reminder_workflow';
    case Campaign = 'campaign_workflow';
    case Custom = 'custom_workflow';
}
