<?php

declare(strict_types=1);

namespace DressnMore\Aos\ToolRegistry\Domain\Tool;

enum ToolCategory: string
{
    case Customer = 'customer';
    case Reservation = 'reservation';
    case Inventory = 'inventory';
    case Rental = 'rental';
    case Tailoring = 'tailoring';
    case Invoices = 'invoices';
    case Payments = 'payments';
    case Accounting = 'accounting';
    case Cashbox = 'cashbox';
    case Hr = 'hr';
    case Reports = 'reports';
    case Analytics = 'analytics';
    case Marketing = 'marketing';
    case Notification = 'notification';
    case Communication = 'communication';
    case Knowledge = 'knowledge';
    case Workflow = 'workflow';
    case Automation = 'automation';
    case Ai = 'ai';
    case Utilities = 'utilities';
}
