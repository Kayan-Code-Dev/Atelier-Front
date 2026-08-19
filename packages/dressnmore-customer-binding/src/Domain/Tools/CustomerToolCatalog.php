<?php

declare(strict_types=1);

namespace DressnMore\CustomerBinding\Domain\Tools;

use DressnMore\Aos\Tools\Domain\Tool\ToolRiskLevel;

/**
 * Catalog of Customer Business Tool contracts (binding layer — no domain execution).
 */
final class CustomerToolCatalog
{
    /**
     * @return list<CustomerToolContract>
     */
    public static function all(): array
    {
        return [
            new CustomerToolContract(
                CustomerToolName::GetCustomer,
                'Resolve and return a customer profile safe for AI consumption',
                ['customerRef|channelContactId', 'tenantId'],
                ['customer profile DTO'],
                'read_customer_profile',
                'customer.read',
                ToolRiskLevel::Low,
                ApprovalPolicy::None,
                ['CustomerResolved', 'CustomerContextBuilt'],
            ),
            new CustomerToolContract(
                CustomerToolName::SearchCustomer,
                'Search customers by phone, name, or channel identity',
                ['query', 'tenantId', 'limit?'],
                ['candidates[]'],
                'search_customers',
                'customer.search',
                ToolRiskLevel::Low,
                ApprovalPolicy::None,
                ['CustomerResolved'],
            ),
            new CustomerToolContract(
                CustomerToolName::CreateCustomer,
                'Create a new customer twin within tenant policy',
                ['profile fields', 'tenantId', 'idempotencyKey?'],
                ['customerRef'],
                'write_customer_profile',
                'customer.create',
                ToolRiskLevel::Medium,
                ApprovalPolicy::Often,
                ['CustomerCreated', 'CustomerResolved'],
            ),
            new CustomerToolContract(
                CustomerToolName::UpdateCustomer,
                'Update bounded customer profile fields',
                ['customerRef', 'patch fields', 'tenantId'],
                ['updated customer'],
                'write_customer_profile',
                'customer.update',
                ToolRiskLevel::Medium,
                ApprovalPolicy::Often,
                ['CustomerUpdated'],
            ),
            new CustomerToolContract(
                CustomerToolName::GetCustomerHistory,
                'Return summarized historical interactions and commercial history',
                ['customerRef', 'tenantId', 'window?'],
                ['history summary'],
                'read_customer_history',
                'customer.history.read',
                ToolRiskLevel::Low,
                ApprovalPolicy::None,
                ['CustomerSummaryBuilt'],
            ),
            new CustomerToolContract(
                CustomerToolName::GetCustomerMeasurements,
                'Return measurement profile for tailoring context',
                ['customerRef', 'tenantId'],
                ['measurements'],
                'read_measurements',
                'customer.measurements.read',
                ToolRiskLevel::Low,
                ApprovalPolicy::None,
                ['CustomerContextBuilt'],
            ),
            new CustomerToolContract(
                CustomerToolName::GetCustomerReservations,
                'List reservations linked to the customer',
                ['customerRef', 'tenantId', 'status?'],
                ['reservations[]'],
                'read_schedule',
                'customer.reservations.read',
                ToolRiskLevel::Low,
                ApprovalPolicy::None,
                ['CustomerContextBuilt'],
            ),
            new CustomerToolContract(
                CustomerToolName::GetCustomerInvoices,
                'List invoices for the customer',
                ['customerRef', 'tenantId', 'status?'],
                ['invoices[]'],
                'read_invoice',
                'customer.invoices.read',
                ToolRiskLevel::Low,
                ApprovalPolicy::None,
                ['CustomerContextBuilt'],
            ),
            new CustomerToolContract(
                CustomerToolName::GetCustomerOrders,
                'List orders (rental/tailoring/sales) for the customer',
                ['customerRef', 'tenantId', 'status?'],
                ['orders[]'],
                'read_order_status',
                'customer.orders.read',
                ToolRiskLevel::Low,
                ApprovalPolicy::None,
                ['CustomerContextBuilt'],
            ),
            new CustomerToolContract(
                CustomerToolName::GetCustomerNotes,
                'Return staff/AI notes attached to the customer',
                ['customerRef', 'tenantId'],
                ['notes[]'],
                'read_customer_notes',
                'customer.notes.read',
                ToolRiskLevel::Low,
                ApprovalPolicy::None,
                ['CustomerContextBuilt'],
            ),
            new CustomerToolContract(
                CustomerToolName::GetCustomerTimeline,
                'Build omnichannel + commercial timeline for the customer',
                ['customerRef', 'tenantId', 'limit?'],
                ['timeline entries[]'],
                'read_customer_timeline',
                'customer.timeline.read',
                ToolRiskLevel::Low,
                ApprovalPolicy::None,
                ['CustomerTimelineBuilt'],
            ),
            new CustomerToolContract(
                CustomerToolName::CustomerExists,
                'Check whether a customer exists for given identity keys',
                ['phone|channelContactId', 'tenantId'],
                ['exists:bool', 'customerRef?'],
                'search_customers',
                'customer.exists',
                ToolRiskLevel::Low,
                ApprovalPolicy::None,
                ['CustomerResolved'],
            ),
            new CustomerToolContract(
                CustomerToolName::MergeCustomers,
                'Merge duplicate customer twins under policy',
                ['sourceCustomerRef', 'targetCustomerRef', 'tenantId'],
                ['mergedCustomerRef'],
                'merge_customers',
                'customer.merge',
                ToolRiskLevel::Critical,
                ApprovalPolicy::Always,
                ['CustomerMerged', 'CustomerUpdated'],
            ),
            new CustomerToolContract(
                CustomerToolName::CustomerSummary,
                'Produce an AI-facing customer summary snapshot',
                ['customerRef', 'tenantId'],
                ['summary text/structure'],
                'read_customer_summary',
                'customer.summary.read',
                ToolRiskLevel::Low,
                ApprovalPolicy::None,
                ['CustomerSummaryBuilt', 'CustomerSnapshotBuilt'],
            ),
            new CustomerToolContract(
                CustomerToolName::CustomerInsights,
                'Return conceptual insights placeholders for planner/prompt',
                ['customerRef', 'tenantId'],
                ['insights[]'],
                'read_customer_insights',
                'customer.insights.read',
                ToolRiskLevel::Low,
                ApprovalPolicy::None,
                ['CustomerSummaryBuilt'],
            ),
        ];
    }

    public static function get(CustomerToolName $name): CustomerToolContract
    {
        foreach (self::all() as $contract) {
            if ($contract->name() === $name) {
                return $contract;
            }
        }

        throw new \InvalidArgumentException('Unknown customer tool: '.$name->value);
    }
}
