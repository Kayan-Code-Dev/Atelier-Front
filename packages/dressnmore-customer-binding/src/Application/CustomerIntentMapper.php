<?php

declare(strict_types=1);

namespace DressnMore\CustomerBinding\Application;

use DressnMore\CustomerBinding\Contracts\CustomerIntentMapperInterface;
use DressnMore\CustomerBinding\Domain\Tools\CustomerToolName;

final class CustomerIntentMapper implements CustomerIntentMapperInterface
{
    public function map(string $intent): ?string
    {
        $key = strtolower(trim($intent));

        return match ($key) {
            'get_customer', 'who_is_customer', 'customer_profile' => CustomerToolName::GetCustomer->value,
            'search_customer', 'find_customer' => CustomerToolName::SearchCustomer->value,
            'create_customer', 'new_customer' => CustomerToolName::CreateCustomer->value,
            'update_customer' => CustomerToolName::UpdateCustomer->value,
            'customer_history' => CustomerToolName::GetCustomerHistory->value,
            'customer_measurements', 'measurements' => CustomerToolName::GetCustomerMeasurements->value,
            'customer_reservations' => CustomerToolName::GetCustomerReservations->value,
            'customer_invoices' => CustomerToolName::GetCustomerInvoices->value,
            'customer_orders' => CustomerToolName::GetCustomerOrders->value,
            'customer_notes' => CustomerToolName::GetCustomerNotes->value,
            'customer_timeline' => CustomerToolName::GetCustomerTimeline->value,
            'customer_exists' => CustomerToolName::CustomerExists->value,
            'merge_customers' => CustomerToolName::MergeCustomers->value,
            'customer_summary' => CustomerToolName::CustomerSummary->value,
            'customer_insights' => CustomerToolName::CustomerInsights->value,
            default => null,
        };
    }
}
