<?php

namespace App\Support\TrialOnboarding;

enum TrialOnboardingEventName: string
{
    case Started = 'trial_onboarding_started';
    case BranchCreated = 'trial_branch_created';
    case CashboxCreated = 'trial_cashbox_created';
    case SupplierCreated = 'trial_supplier_created';
    case PurchaseOrderCreated = 'trial_purchase_order_created';
    case PurchaseOrderReceived = 'trial_purchase_order_received';
    case InventoryVerified = 'trial_inventory_verified';
    case CustomerCreated = 'trial_customer_created';
    case ReservationCreated = 'trial_reservation_created';
    case BalancesViewed = 'trial_balances_viewed';
    case StatementViewed = 'trial_statement_viewed';
    case Completed = 'trial_onboarding_completed';
    case PricingViewed = 'trial_pricing_viewed';
    case UpgradeClicked = 'trial_upgrade_clicked';
    case CheckoutStarted = 'trial_checkout_started';

    public function isCommercialSignal(): bool
    {
        return in_array($this, [self::PricingViewed, self::UpgradeClicked, self::CheckoutStarted], true);
    }
}
