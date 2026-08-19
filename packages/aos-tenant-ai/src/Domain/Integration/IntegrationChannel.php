<?php

declare(strict_types=1);

namespace DressnMore\Aos\TenantAi\Domain\Integration;

enum IntegrationChannel: string
{
    case WhatsApp = 'whatsapp';
    case Facebook = 'facebook';
    case Instagram = 'instagram';
    case Email = 'email';
    case GoogleCalendar = 'google_calendar';
    case GoogleDrive = 'google_drive';
    case Sms = 'sms';
    case PaymentProviders = 'payment_providers';
}
