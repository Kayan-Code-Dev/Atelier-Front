<?php

declare(strict_types=1);

namespace DressnMore\Aos\Prompts\Domain\Persona;

enum PersonaType: string
{
    case SalesAgent = 'sales_agent';
    case SupportAgent = 'support_agent';
    case ReceptionAgent = 'reception_agent';
    case ReservationAgent = 'reservation_agent';
    case MarketingAgent = 'marketing_agent';
    case AdminAssistant = 'admin_assistant';
    case AnalyticsAssistant = 'analytics_assistant';
    case Custom = 'custom';
}
