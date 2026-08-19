<?php

namespace App\Services\Tenant;

use App\Models\Tenant\Branch;
use App\Models\Tenant\Cashbox;
use App\Models\Tenant\CashMovement;
use App\Models\Tenant\Customer;
use App\Models\Tenant\Dress;
use App\Models\Tenant\Invoice;
use App\Models\Tenant\InventoryMovement;
use App\Models\Tenant\PurchaseOrder;
use App\Models\Tenant\Supplier;
use App\Support\TrialOnboarding\TrialOnboardingStepKey;

class TrialOnboardingEvaluator
{
    /**
     * @param  list<string>  $viewedSteps
     * @return array<string, bool>
     */
    public function evaluate(array $viewedSteps = []): array
    {
        $viewed = array_flip($viewedSteps);
        $branchExists = Branch::query()->exists();
        $cashboxExists = Cashbox::query()->exists();
        $receivedExists = PurchaseOrder::query()->whereNotNull('received_at')->exists();
        $inventoryExists = Dress::query()->where('entity_type', 'purchase_order')->exists()
            || InventoryMovement::query()->where('reason', 'purchase_order_received')->exists();

        return [
            TrialOnboardingStepKey::BranchSetup->value => $branchExists,
            TrialOnboardingStepKey::CashboxSetup->value => $branchExists && $cashboxExists,
            TrialOnboardingStepKey::SupplierSetup->value => Supplier::query()->exists(),
            TrialOnboardingStepKey::PurchaseOrderCreated->value => PurchaseOrder::query()->exists(),
            TrialOnboardingStepKey::PurchaseOrderReceived->value => $receivedExists,
            TrialOnboardingStepKey::InventoryVerified->value => $inventoryExists,
            TrialOnboardingStepKey::CustomerSetup->value => Customer::query()->exists(),
            TrialOnboardingStepKey::ReservationCreated->value => Invoice::query()->where('type', Invoice::TYPE_RENT)->exists(),
            TrialOnboardingStepKey::BalancesReview->value => isset($viewed[TrialOnboardingStepKey::BalancesReview->value]),
            TrialOnboardingStepKey::AccountStatement->value => isset($viewed[TrialOnboardingStepKey::AccountStatement->value]),
        ];
    }

    /**
     * @return array<string, int>
     */
    public function summaryCounts(): array
    {
        $inventoryCount = (int) InventoryMovement::query()
            ->where('reason', 'purchase_order_received')
            ->sum('quantity');
        if ($inventoryCount === 0) {
            $inventoryCount = Dress::query()->where('entity_type', 'purchase_order')->count();
        }

        return [
            'branches' => Branch::query()->count(),
            'cashboxes' => Cashbox::query()->count(),
            'suppliers' => Supplier::query()->count(),
            'purchase_orders' => PurchaseOrder::query()->count(),
            'received_orders' => PurchaseOrder::query()->whereNotNull('received_at')->count(),
            'inventory_items' => $inventoryCount,
            'products' => Dress::query()->count(),
            'customers' => Customer::query()->count(),
            'reservations' => Invoice::query()->where('type', Invoice::TYPE_RENT)->count(),
            'financial_activities' => CashMovement::query()->count(),
        ];
    }
}
