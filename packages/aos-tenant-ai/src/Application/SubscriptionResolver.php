<?php

declare(strict_types=1);

namespace DressnMore\Aos\TenantAi\Application;

use DressnMore\Aos\TenantAi\Contracts\SubscriptionProviderInterface;
use DressnMore\Aos\TenantAi\Domain\Policies\TenantAiPolicies;
use DressnMore\Aos\TenantAi\Domain\Subscription\SubscriptionEntitlement;
use DressnMore\Aos\TenantAi\Domain\Subscription\SubscriptionPlan;

final class SubscriptionResolver implements SubscriptionProviderInterface
{
    public function __construct(private readonly TenantAiPolicies $policies = new TenantAiPolicies()) {}

    public function resolve(SubscriptionPlan|string $plan): SubscriptionEntitlement
    {
        $planEnum = $plan instanceof SubscriptionPlan ? $plan : SubscriptionPlan::from(strtolower((string) $plan));

        return match ($planEnum) {
            SubscriptionPlan::Basic => new SubscriptionEntitlement(
                SubscriptionPlan::Basic,
                ['customer', 'reservation'],
                ['Customer.Read', 'Customer.Write', 'Customer.Search', 'Reservation.Read', 'Reservation.Create', 'Reservation.Update'],
                ['GetCustomer', 'SearchCustomer', 'CreateCustomer', 'CheckAvailability', 'CreateReservation', 'CancelReservation'],
            ),
            SubscriptionPlan::Professional => new SubscriptionEntitlement(
                SubscriptionPlan::Professional,
                ['customer', 'reservation', 'inventory', 'reports', 'analytics'],
                ['Customer.Read', 'Customer.Write', 'Reservation.Read', 'Reservation.Create', 'Inventory.Read', 'Reports.Read', 'Analytics.Read'],
                ['GetCustomer', 'CreateReservation', 'GetInventory', 'GenerateReport', 'CustomerInsights'],
            ),
            SubscriptionPlan::Enterprise => new SubscriptionEntitlement(
                SubscriptionPlan::Enterprise,
                ['*'],
                ['*'],
                ['*'],
            ),
        };
    }

    public function assertToolAllowed(SubscriptionEntitlement $entitlement, string $toolName): void
    {
        $this->policies->assertSubscription($entitlement, $toolName);
    }
}
