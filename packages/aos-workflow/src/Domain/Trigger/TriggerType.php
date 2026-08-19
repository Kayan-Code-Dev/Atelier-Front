<?php

declare(strict_types=1);

namespace DressnMore\Aos\Workflow\Domain\Trigger;

enum TriggerType: string
{
    case IncomingMessage = 'incoming_message';
    case Comment = 'comment';
    case LeadCreated = 'lead_created';
    case CustomerCreated = 'customer_created';
    case ReservationCreated = 'reservation_created';
    case InvoiceCreated = 'invoice_created';
    case PaymentReceived = 'payment_received';
    case TimeTrigger = 'time_trigger';
    case CronTrigger = 'cron_trigger';
    case ManualTrigger = 'manual_trigger';
    case ApiTriggerFuture = 'api_trigger_future';
}
