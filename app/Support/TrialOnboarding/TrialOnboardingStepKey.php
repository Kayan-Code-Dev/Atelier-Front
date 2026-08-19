<?php

namespace App\Support\TrialOnboarding;

enum TrialOnboardingStepKey: string
{
    case BranchSetup = 'branch_setup';
    case CashboxSetup = 'cashbox_setup';
    case SupplierSetup = 'supplier_setup';
    case PurchaseOrderCreated = 'purchase_order_created';
    case PurchaseOrderReceived = 'purchase_order_received';
    case InventoryVerified = 'inventory_verified';
    case CustomerSetup = 'customer_setup';
    case ReservationCreated = 'reservation_created';
    case BalancesReview = 'balances_review';
    case AccountStatement = 'account_statement';

    public function order(): int
    {
        return match ($this) {
            self::BranchSetup => 1,
            self::CashboxSetup => 2,
            self::SupplierSetup => 3,
            self::PurchaseOrderCreated => 4,
            self::PurchaseOrderReceived => 5,
            self::InventoryVerified => 6,
            self::CustomerSetup => 7,
            self::ReservationCreated => 8,
            self::BalancesReview => 9,
            self::AccountStatement => 10,
        };
    }

    public function title(): string
    {
        return match ($this) {
            self::BranchSetup => 'أنشئ أول فرع',
            self::CashboxSetup => 'أنشئ خزنتك',
            self::SupplierSetup => 'أضف موردًا',
            self::PurchaseOrderCreated => 'أنشئ أول طلبية',
            self::PurchaseOrderReceived => 'استلم الطلبية',
            self::InventoryVerified => 'تأكد أن المنتجات ظهرت في المخزون',
            self::CustomerSetup => 'أضف عميلة',
            self::ReservationCreated => 'أنشئ حجزًا',
            self::BalancesReview => 'راجع الأرصدة',
            self::AccountStatement => 'اطلع على كشف الحساب',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::BranchSetup => 'هذا الفرع سيكون الأساس الذي ستبني عليه باقي عمليات الأتيليه.',
            self::CashboxSetup => 'الخزنة تسجّل المقبوضات والمدفوعات لكل فرع.',
            self::SupplierSetup => 'أضف مورد الأقمشة أو القطع الذي ستشتري منه.',
            self::PurchaseOrderCreated => 'أنشئ طلبية شراء لدخول القطع إلى دورة العمل.',
            self::PurchaseOrderReceived => 'استلم الطلبية حتى تُضاف القطع إلى المخزون تلقائيًا.',
            self::InventoryVerified => 'بعد الاستلام تظهر المنتجات في قائمة المخزون.',
            self::CustomerSetup => 'أضيفي بيانات العميلة قبل إنشاء الحجز.',
            self::ReservationCreated => 'أنشئ حجز إيجار لتجربة دورة العميل الكاملة.',
            self::BalancesReview => 'راجع أرصدة الخزنة بعد العمليات.',
            self::AccountStatement => 'اطلع على كشف حساب المورد بعد الشراء والاستلام.',
        };
    }

    public function requiredAction(): string
    {
        return match ($this) {
            self::BranchSetup => 'create_branch',
            self::CashboxSetup => 'create_cashbox',
            self::SupplierSetup => 'create_supplier',
            self::PurchaseOrderCreated => 'create_purchase_order',
            self::PurchaseOrderReceived => 'receive_purchase_order',
            self::InventoryVerified => 'verify_inventory',
            self::CustomerSetup => 'create_customer',
            self::ReservationCreated => 'create_reservation',
            self::BalancesReview => 'view_balances',
            self::AccountStatement => 'view_statement',
        };
    }

    public function completionCondition(): string
    {
        return match ($this) {
            self::BranchSetup => 'branch exists for current trial tenant',
            self::CashboxSetup => 'current branch has cashbox',
            self::SupplierSetup => 'supplier exists',
            self::PurchaseOrderCreated => 'purchase order exists',
            self::PurchaseOrderReceived => 'purchase order received_at is set',
            self::InventoryVerified => 'received purchase items exist in inventory',
            self::CustomerSetup => 'customer exists',
            self::ReservationCreated => 'rental reservation exists',
            self::BalancesReview => 'balances page successfully viewed',
            self::AccountStatement => 'account statement successfully viewed',
        };
    }

    public function route(): string
    {
        return match ($this) {
            self::BranchSetup => '/branches',
            self::CashboxSetup => '/cashboxes',
            self::SupplierSetup => '/suppliers',
            self::PurchaseOrderCreated => '/purchase-orders',
            self::PurchaseOrderReceived => '/purchase-orders',
            self::InventoryVerified => '/dresses',
            self::CustomerSetup => '/customers',
            self::ReservationCreated => '/orders/create-order',
            self::BalancesReview => '/cashboxes',
            self::AccountStatement => '/suppliers/accounts',
        };
    }

    public function event(): TrialOnboardingEventName
    {
        return match ($this) {
            self::BranchSetup => TrialOnboardingEventName::BranchCreated,
            self::CashboxSetup => TrialOnboardingEventName::CashboxCreated,
            self::SupplierSetup => TrialOnboardingEventName::SupplierCreated,
            self::PurchaseOrderCreated => TrialOnboardingEventName::PurchaseOrderCreated,
            self::PurchaseOrderReceived => TrialOnboardingEventName::PurchaseOrderReceived,
            self::InventoryVerified => TrialOnboardingEventName::InventoryVerified,
            self::CustomerSetup => TrialOnboardingEventName::CustomerCreated,
            self::ReservationCreated => TrialOnboardingEventName::ReservationCreated,
            self::BalancesReview => TrialOnboardingEventName::BalancesViewed,
            self::AccountStatement => TrialOnboardingEventName::StatementViewed,
        };
    }

    public function successCopy(): string
    {
        return match ($this) {
            self::BranchSetup => 'ممتاز! الآن أنشئ خزنتك.',
            self::CashboxSetup => 'تم. أضف موردًا لتبدأ الشراء.',
            self::SupplierSetup => 'رائع. أنشئ أول طلبية شراء.',
            self::PurchaseOrderCreated => 'جيد. استلم الطلبية لتدخل القطع للمخزون.',
            self::PurchaseOrderReceived => 'تم الاستلام. تأكد أن المنتجات ظهرت في المخزون.',
            self::InventoryVerified => 'المخزون جاهز. أضف عميلة الآن.',
            self::CustomerSetup => 'تم. أنشئ حجزًا للعميلة.',
            self::ReservationCreated => 'دورة التشغيل اكتملت تقريبًا. راجع الأرصدة.',
            self::BalancesReview => 'الآن اطلع على كشف الحساب.',
            self::AccountStatement => 'أحسنت! أكملت دورة تشغيل الأتيليه.',
        };
    }

    public function target(): string
    {
        return $this->value;
    }

    public function isViewStep(): bool
    {
        return $this === self::BalancesReview || $this === self::AccountStatement;
    }

    /**
     * @return list<self>
     */
    public static function ordered(): array
    {
        $steps = self::cases();
        usort($steps, fn (self $a, self $b): int => $a->order() <=> $b->order());

        return $steps;
    }
}
