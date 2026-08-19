<?php

use App\Http\Controllers\Tenant\AccountingController;
use App\Http\Controllers\Tenant\AccountingPeriodController;
use App\Http\Controllers\Tenant\AuthController;
use App\Http\Controllers\Tenant\BankAccountController;
use App\Http\Controllers\Tenant\BankReconciliationController;
use App\Http\Controllers\Tenant\BranchController;
use App\Http\Controllers\Tenant\CashboxController;
use App\Http\Controllers\Tenant\CashMovementController;
use App\Http\Controllers\Tenant\CustomerController;
use App\Http\Controllers\Tenant\DashboardController;
use App\Http\Controllers\Tenant\DeliveryWorkflowController;
use App\Http\Controllers\Tenant\DressCategoryController;
use App\Http\Controllers\Tenant\DressController;
use App\Http\Controllers\Tenant\EmployeeController;
use App\Http\Controllers\Tenant\ExpenseCategoryController;
use App\Http\Controllers\Tenant\ExpenseController;
use App\Http\Controllers\Tenant\EquityController;
use App\Http\Controllers\Tenant\FactoryController;
use App\Http\Controllers\Tenant\FixedAssetController;
use App\Http\Controllers\Tenant\HealthController;
use App\Http\Controllers\Tenant\HrDashboardController;
use App\Http\Controllers\Tenant\HrAccessController;
use App\Http\Controllers\Tenant\HrAttendanceController;
use App\Http\Controllers\Tenant\HrDepartmentController;
use App\Http\Controllers\Tenant\HrDocumentController;
use App\Http\Controllers\Tenant\HrEmployeeController;
use App\Http\Controllers\Tenant\HrJobTitleController;
use App\Http\Controllers\Tenant\HrLeaveController;
use App\Http\Controllers\Tenant\HrEmployeeNoteController;
use App\Http\Controllers\Tenant\HrPayrollAdjustmentController;
use App\Http\Controllers\Tenant\HrPayrollController;
use App\Http\Controllers\Tenant\HrSettingController;
use App\Http\Controllers\Tenant\HrShiftController;
use App\Http\Controllers\Tenant\InvoiceController;
use App\Http\Controllers\Tenant\InvoiceDeliveryController;
use App\Http\Controllers\Tenant\JournalEntryController;
use App\Http\Controllers\Tenant\LiabilityController;
use App\Http\Controllers\Tenant\LookupController;
use App\Http\Controllers\Tenant\NotificationController;
use App\Http\Controllers\Tenant\OpeningBalanceController;
use App\Http\Controllers\Tenant\PayableController;
use App\Http\Controllers\Tenant\PaymentController;
use App\Http\Controllers\Tenant\PurchaseOrderController;
use App\Http\Controllers\Tenant\RentalOrderController;
use App\Http\Controllers\Tenant\RentalReturnSettlementController;
use App\Http\Controllers\Tenant\ReceivableController;
use App\Http\Controllers\Tenant\ReportController;
use App\Http\Controllers\Tenant\SalesController;
use App\Http\Controllers\Tenant\SettingsController;
use App\Http\Controllers\Tenant\SubscriptionController;
use App\Http\Controllers\Tenant\TransactionStatementController;
use App\Http\Controllers\Tenant\TrialOnboardingController;
use App\Http\Controllers\Tenant\SupplierController;
use App\Http\Controllers\Tenant\SupplierPaymentController;
use App\Http\Controllers\Tenant\TailoringOrderController;
use App\Http\Controllers\Tenant\WorkshopController;
use App\Http\Controllers\Tenant\EmployeeActivityController;
use App\Http\Controllers\Tenant\Intelligence\IntelligenceController;
use App\Http\Controllers\Tenant\Intelligence\IntelligenceInsightsController;
use App\Http\Controllers\Tenant\Website\WebsiteController;
use App\Http\Controllers\Tenant\Marketplace\MarketplaceController;
use Illuminate\Support\Facades\Route;

Route::prefix('tenant')->group(function (): void {
    Route::get('/health', [HealthController::class, 'index'])
        ->middleware(['identify.tenant', 'check.tenant.subscription', 'set.tenant.database']);

    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/login/google', [AuthController::class, 'loginWithGoogle']);
    Route::get('/auth/google', [AuthController::class, 'googleConfig']);

    Route::middleware([
        'identify.tenant',
        'set.tenant.database',
        'auth:sanctum',
        'ensure.tenant.token',
    ])->group(function (): void {
        // /me must work even when subscription checks would block other APIs,
        // otherwise a page refresh falsely logs the user out.
        Route::get('/me', [AuthController::class, 'me']);
        Route::get('/subscription/overview', [SubscriptionController::class, 'overview']);
        Route::get('/subscription/usage', [SubscriptionController::class, 'usage']);
        Route::get('/subscription/feature-catalog', [SubscriptionController::class, 'featureCatalog']);
        Route::get('/subscription/payment-gateways', [SubscriptionController::class, 'paymentGateways']);
        Route::post('/subscription/renew', [SubscriptionController::class, 'renew']);
        Route::post('/subscription/change-request', [SubscriptionController::class, 'submitChangeRequest']);
        Route::post('/subscription/upgrade', [SubscriptionController::class, 'upgrade']);
        Route::get('/settings/profile', [SettingsController::class, 'profile']);
        Route::put('/settings/profile', [SettingsController::class, 'updateProfile']);
        Route::post('/settings/profile/avatar', [SettingsController::class, 'uploadAvatar']);
        Route::put('/settings/password', [SettingsController::class, 'updatePassword']);
        Route::delete('/settings/account', [SettingsController::class, 'deleteAccount']);
        Route::get('/settings/data-reset/preview', [SettingsController::class, 'dataResetPreview']);
        Route::post('/settings/data-reset', [SettingsController::class, 'resetData']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });

    Route::middleware([
        'identify.tenant',
        'check.tenant.subscription',
        'set.tenant.database',
        'auth:sanctum',
        'ensure.tenant.token',
        'log.employee.activity',
    ])->group(function (): void {
        Route::get('/lookups', [LookupController::class, 'index']);

        Route::prefix('/trial-onboarding')->group(function (): void {
            Route::get('/', [TrialOnboardingController::class, 'show']);
            Route::post('/start', [TrialOnboardingController::class, 'start']);
            Route::post('/views', [TrialOnboardingController::class, 'view']);
            Route::post('/signals', [TrialOnboardingController::class, 'signal']);
            Route::post('/acknowledge-completion', [TrialOnboardingController::class, 'acknowledge']);
        });
        Route::get('/settings/app', [SettingsController::class, 'appSettings'])
            ->middleware('tenant.permission:settings.view');
        Route::put('/settings/app', [SettingsController::class, 'updateAppSettings'])
            ->middleware('tenant.permission:settings.manage');

        Route::prefix('/orders')->middleware(['plan.feature:invoices.enabled', 'tenant.permission:invoices.view'])->group(function (): void {
            Route::get('/rental/stats', [RentalOrderController::class, 'stats']);
            Route::get('/rental', [RentalOrderController::class, 'index']);
            Route::get('/rental/{invoice}', [RentalOrderController::class, 'show'])
                ->whereNumber('invoice');
            Route::get('/delivery-search', [DeliveryWorkflowController::class, 'search'])
                ->middleware(['tenant.permission:invoice_delivery.view', 'plan.feature:deliveries.enabled']);
        });

        Route::get('/deliveries/stats', [DeliveryWorkflowController::class, 'invoiceDeliveryStats'])
            ->middleware(['tenant.permission:invoice_delivery.view', 'plan.feature:deliveries.enabled']);
        Route::get('/deliveries', [DeliveryWorkflowController::class, 'deliveries'])
            ->middleware(['tenant.permission:invoice_delivery.view', 'plan.feature:deliveries.enabled']);
        Route::get('/returns/stats', [DeliveryWorkflowController::class, 'invoiceReturnStats'])
            ->middleware(['tenant.permission:invoice_delivery.view', 'plan.feature:returns.enabled']);
        Route::get('/returns', [DeliveryWorkflowController::class, 'returns'])
            ->middleware(['tenant.permission:invoice_delivery.view', 'plan.feature:returns.enabled']);
        Route::get('/returns/overdue', [DeliveryWorkflowController::class, 'overdue'])
            ->middleware(['tenant.permission:invoice_delivery.view', 'plan.feature:returns.enabled']);

        Route::get('/returns/{invoice}/settlement-preview', [RentalReturnSettlementController::class, 'preview'])
            ->whereNumber('invoice')
            ->middleware(['tenant.permission:invoice_delivery.return', 'plan.feature:returns.enabled']);
        Route::post('/returns/{invoice}/settle', [RentalReturnSettlementController::class, 'settle'])
            ->whereNumber('invoice')
            ->middleware(['tenant.permission:invoice_delivery.return', 'plan.feature:returns.enabled']);

        Route::get('/supplier-payments', [SupplierPaymentController::class, 'index'])
            ->middleware(['tenant.permission:supplier_payments.view', 'plan.feature:supplier_payments.enabled']);

        Route::prefix('/tailoring')->middleware(['plan.feature:invoices.enabled', 'tenant.permission:tailoring.view'])->group(function (): void {
            Route::get('/orders/stats', [TailoringOrderController::class, 'stats']);
            Route::get('/orders', [TailoringOrderController::class, 'index']);
            Route::post('/orders', [TailoringOrderController::class, 'store'])
                ->middleware('tenant.permission:tailoring.create');
            Route::get('/orders/{invoice}', [TailoringOrderController::class, 'show'])
                ->whereNumber('invoice');
            Route::patch('/orders/{invoice}', [TailoringOrderController::class, 'update'])
                ->whereNumber('invoice')
                ->middleware('tenant.permission:tailoring.update');
            Route::post('/orders/{invoice}/change-stage', [TailoringOrderController::class, 'changeStage'])
                ->whereNumber('invoice')
                ->middleware('tenant.permission:tailoring.change_stage');
            Route::get('/orders/{invoice}/stage-history', [TailoringOrderController::class, 'stageHistory'])
                ->whereNumber('invoice');
            Route::put('/orders/{invoice}/measurements', [TailoringOrderController::class, 'updateMeasurements'])
                ->whereNumber('invoice')
                ->middleware('tenant.permission:tailoring.update');
            Route::post('/orders/{invoice}/cancel', [TailoringOrderController::class, 'cancel'])
                ->whereNumber('invoice')
                ->middleware('tenant.permission:tailoring.update');
            Route::get('/workshop-board', [TailoringOrderController::class, 'workshopBoard'])
                ->middleware('tenant.permission:tailoring.view_workshop');
            Route::get('/schedule', [TailoringOrderController::class, 'schedule'])
                ->middleware('tenant.permission:tailoring.view_schedule');
            Route::get('/deliveries', [TailoringOrderController::class, 'deliveries']);
        });

        Route::prefix('/sales')->middleware('plan.feature:invoices.enabled')->group(function (): void {
            Route::get('/reports/summary', [SalesController::class, 'reportSummary'])
                ->middleware('tenant.permission:reports.sales');
            Route::get('/reports/daily', [SalesController::class, 'reportDaily'])
                ->middleware('tenant.permission:reports.sales');
            Route::get('/reports/products', [SalesController::class, 'reportProducts'])
                ->middleware('tenant.permission:reports.sales');
            Route::get('/reports/by-employee', [SalesController::class, 'reportByEmployee'])
                ->middleware('tenant.permission:reports.sales');
            Route::get('/invoices/stats', [SalesController::class, 'invoiceStats'])
                ->middleware('tenant.permission:invoices.view');
            Route::get('/invoices', [SalesController::class, 'indexInvoices'])
                ->middleware('tenant.permission:invoices.view');
            Route::get('/invoices/{invoice}', [SalesController::class, 'show'])
                ->whereNumber('invoice')
                ->middleware('tenant.permission:invoices.view');
            Route::post('/invoices', [SalesController::class, 'storeInvoice'])
                ->middleware('tenant.permission:invoices.create');
        });

        Route::prefix('/employees')->group(function (): void {
            Route::get('/', [EmployeeController::class, 'index'])
                ->middleware('tenant.permission:users.manage');
            Route::get('/custodies', [EmployeeController::class, 'custodies'])
                ->middleware('tenant.permission:users.manage');
            Route::get('/salaries', [EmployeeController::class, 'salaries'])
                ->middleware('tenant.permission:users.manage');
            Route::get('/{employee}', [EmployeeController::class, 'show'])
                ->whereNumber('employee')
                ->middleware('tenant.permission:users.manage');
        });

        Route::prefix('/workshops')->middleware('plan.feature:workshop.enabled')->group(function (): void {
            Route::get('/', [WorkshopController::class, 'index'])
                ->middleware('tenant.permission:settings.manage');
            Route::get('/{workshop}/transfers', [WorkshopController::class, 'transfers'])
                ->whereNumber('workshop')
                ->middleware('tenant.permission:settings.manage');
            Route::get('/{workshop}/cloths', [WorkshopController::class, 'cloths'])
                ->whereNumber('workshop')
                ->middleware('tenant.permission:settings.manage');
            Route::get('/{workshop}', [WorkshopController::class, 'show'])
                ->whereNumber('workshop')
                ->middleware('tenant.permission:settings.manage');
        });

        Route::get('/factories', [FactoryController::class, 'index'])
            ->middleware(['plan.feature:factory.enabled', 'tenant.permission:settings.manage']);

        Route::prefix('/notifications')->group(function (): void {
            Route::get('/', [NotificationController::class, 'index'])
                ->middleware('tenant.permission:notifications.view');
            Route::get('/stats', [NotificationController::class, 'stats'])
                ->middleware('tenant.permission:notifications.view');
            Route::post('/read-all', [NotificationController::class, 'markAllRead'])
                ->middleware('tenant.permission:notifications.view');
            Route::patch('/{notification}/read', [NotificationController::class, 'markRead'])
                ->whereNumber('notification')
                ->middleware('tenant.permission:notifications.view');
            Route::delete('/{notification}', [NotificationController::class, 'destroy'])
                ->whereNumber('notification')
                ->middleware('tenant.permission:notifications.view');
        });

        Route::prefix('/dashboard')->middleware('plan.feature:dashboard.enabled')->group(function (): void {
            Route::get('/overview', [DashboardController::class, 'overview'])
                ->middleware('tenant.permission:dashboard.view');
        });

        Route::prefix('/reports')->middleware('plan.feature:reports.enabled')->group(function (): void {
            Route::get('/catalog', [ReportController::class, 'catalog'])
                ->middleware('tenant.permission:reports.view');
            Route::get('/overview', [ReportController::class, 'overview'])
                ->middleware('tenant.permission:reports.view');
            Route::get('/sales', [ReportController::class, 'sales'])
                ->middleware('tenant.permission:reports.sales');
            Route::get('/tailoring', [ReportController::class, 'tailoring'])
                ->middleware('tenant.permission:reports.tailoring');
            Route::get('/{type}', [ReportController::class, 'show'])
                ->where('type', 'sales-daily|sales-products|sales-employees|rental|deliveries|returns|customers|inventory|expenses|cash|accounting|payments|suppliers')
                ->middleware('tenant.permission:reports.view');
        });

        Route::prefix('/accounting')->middleware('plan.feature:accounting.enabled')->group(function (): void {
            Route::get('/summary', [AccountingController::class, 'summary'])
                ->middleware('tenant.permission:accounting.view');
            Route::get('/ledger', [AccountingController::class, 'ledger'])
                ->middleware('tenant.permission:accounting.view|accounting.entries.view|accounting.journal_entries.view');
            Route::get('/ledger/export', [AccountingController::class, 'ledgerExport'])
                ->middleware('tenant.permission:accounting.reports.export|accounting.entries.export|accounting.journal_entries.export');
            Route::get('/general-ledger', [AccountingController::class, 'generalLedger'])
                ->middleware('tenant.permission:accounting.view|accounting.entries.view');
            Route::get('/general-ledger/export', [AccountingController::class, 'generalLedgerExport'])
                ->middleware('tenant.permission:accounting.reports.export|accounting.entries.export|accounting.journal_entries.export');
            Route::get('/accounts-tree', [AccountingController::class, 'accountsTree'])
                ->middleware('tenant.permission:accounting.view');
            Route::get('/reports/balance-sheet', [AccountingController::class, 'balanceSheet'])
                ->middleware('tenant.permission:accounting.reports.view|accounting.view');
            Route::get('/reports/balance-sheet/export', [AccountingController::class, 'balanceSheetExport'])
                ->middleware('tenant.permission:accounting.reports.export|accounting.view');
            Route::get('/reports/income-statement', [AccountingController::class, 'incomeStatement'])
                ->middleware('tenant.permission:accounting.reports.view|accounting.view');
            Route::get('/reports/income-statement/export', [AccountingController::class, 'incomeStatementExport'])
                ->middleware('tenant.permission:accounting.reports.export|accounting.view');
            Route::get('/reports/trial-balance', [AccountingController::class, 'trialBalance'])
                ->middleware('tenant.permission:accounting.reports.view|accounting.view');
            Route::get('/reports/trial-balance/export', [AccountingController::class, 'trialBalanceExport'])
                ->middleware('tenant.permission:accounting.reports.export|accounting.view');
            Route::get('/reports/cash-flow', [AccountingController::class, 'cashFlow'])
                ->middleware('tenant.permission:accounting.reports.view|accounting.view');
            Route::get('/reports/cash-flow/export', [AccountingController::class, 'cashFlowExport'])
                ->middleware('tenant.permission:accounting.reports.export|accounting.view');
            Route::get('/controls', [AccountingController::class, 'controls'])
                ->middleware('tenant.permission:accounting.controls.view|accounting.view');
            Route::get('/unposted', [AccountingController::class, 'unposted'])
                ->middleware('tenant.permission:accounting.controls.view|accounting.view');
            Route::get('/exceptions', [AccountingController::class, 'exceptions'])
                ->middleware('tenant.permission:accounting.controls.view|accounting.view');
            Route::get('/periods', [AccountingPeriodController::class, 'index'])
                ->middleware('tenant.permission:accounting.periods.view|accounting.view');
            Route::get('/periods/{period}', [AccountingPeriodController::class, 'show'])
                ->whereNumber('period')
                ->middleware('tenant.permission:accounting.periods.view|accounting.view');
            Route::get('/periods/{period}/close-preview', [AccountingPeriodController::class, 'validateClose'])
                ->whereNumber('period')
                ->middleware('tenant.permission:accounting.periods.close|accounting.view');
            Route::post('/periods/{period}/close', [AccountingPeriodController::class, 'close'])
                ->whereNumber('period')
                ->middleware('tenant.permission:accounting.periods.close');
            Route::post('/periods/{period}/reopen', [AccountingPeriodController::class, 'reopen'])
                ->whereNumber('period')
                ->middleware('tenant.permission:accounting.periods.reopen');
            Route::post('/periods/{period}/lock', [AccountingPeriodController::class, 'lock'])
                ->whereNumber('period')
                ->middleware('tenant.permission:accounting.periods.close');

            Route::prefix('/journal-entries')->group(function (): void {
                Route::get('/export', [JournalEntryController::class, 'export'])
                    ->middleware('tenant.permission:accounting.journal_entries.export');
                Route::get('/summary', [JournalEntryController::class, 'summary'])
                    ->middleware('tenant.permission:accounting.journal_entries.view');
                Route::get('/accounts', [JournalEntryController::class, 'accounts'])
                    ->middleware('tenant.permission:accounting.journal_entries.view');
                Route::get('/', [JournalEntryController::class, 'index'])
                    ->middleware('tenant.permission:accounting.journal_entries.view');
                Route::post('/', [JournalEntryController::class, 'store'])
                    ->middleware('tenant.permission:accounting.journal_entries.create');
                Route::get('/{journalEntry}', [JournalEntryController::class, 'show'])
                    ->whereNumber('journalEntry')
                    ->middleware('tenant.permission:accounting.journal_entries.view');
                Route::put('/{journalEntry}', [JournalEntryController::class, 'update'])
                    ->whereNumber('journalEntry')
                    ->middleware('tenant.permission:accounting.journal_entries.update|accounting.journals.update_draft');
                Route::delete('/{journalEntry}', [JournalEntryController::class, 'destroy'])
                    ->whereNumber('journalEntry')
                    ->middleware('tenant.permission:accounting.journal_entries.update|accounting.journals.delete_draft');
                Route::post('/{journalEntry}/approve', [JournalEntryController::class, 'approve'])
                    ->whereNumber('journalEntry')
                    ->middleware('tenant.permission:accounting.journal_entries.approve|accounting.entries.approve|accounting.journals.post');
                Route::post('/{journalEntry}/submit', [JournalEntryController::class, 'submit'])
                    ->whereNumber('journalEntry')
                    ->middleware('tenant.permission:accounting.entries.submit|accounting.journal_entries.create|accounting.journals.create');
                Route::post('/{journalEntry}/accept', [JournalEntryController::class, 'accept'])
                    ->whereNumber('journalEntry')
                    ->middleware('tenant.permission:accounting.entries.approve|accounting.journal_entries.approve|accounting.journals.approve');
                Route::post('/{journalEntry}/post', [JournalEntryController::class, 'post'])
                    ->whereNumber('journalEntry')
                    ->middleware('tenant.permission:accounting.journals.post|accounting.journal_entries.approve|accounting.entries.approve');
                Route::post('/{journalEntry}/cancel', [JournalEntryController::class, 'cancel'])
                    ->whereNumber('journalEntry')
                    ->middleware('tenant.permission:accounting.journal_entries.cancel');
                Route::post('/{journalEntry}/reverse', [JournalEntryController::class, 'reverse'])
                    ->whereNumber('journalEntry')
                    ->middleware('tenant.permission:accounting.journal_entries.reverse|accounting.entries.reverse|accounting.journals.reverse');
            });

            Route::prefix('/opening-balances')->group(function (): void {
                Route::get('/export', [OpeningBalanceController::class, 'export'])
                    ->middleware('tenant.permission:accounting.entries.export|accounting.journal_entries.export|accounting.reports.export');
                Route::get('/', [OpeningBalanceController::class, 'show'])
                    ->middleware('tenant.permission:accounting.entries.view|accounting.journal_entries.view|accounting.view');
                Route::post('/', [OpeningBalanceController::class, 'store'])
                    ->middleware('tenant.permission:accounting.entries.create|accounting.journal_entries.create');
                Route::post('/{batch}/post', [OpeningBalanceController::class, 'post'])
                    ->whereNumber('batch')
                    ->middleware('tenant.permission:accounting.journals.post|accounting.journal_entries.approve|accounting.entries.approve');
            });

            Route::prefix('/asset-categories')->group(function (): void {
                Route::get('/', [FixedAssetController::class, 'categories'])
                    ->middleware('tenant.permission:accounting.assets.view|accounting.view');
                Route::post('/', [FixedAssetController::class, 'saveCategory'])
                    ->middleware('tenant.permission:accounting.assets.create|accounting.assets.edit');
                Route::put('/{category}', [FixedAssetController::class, 'saveCategory'])
                    ->whereNumber('category')
                    ->middleware('tenant.permission:accounting.assets.edit');
            });

            Route::prefix('/assets')->group(function (): void {
                Route::get('/depreciation', [FixedAssetController::class, 'depreciationPreview'])
                    ->middleware('tenant.permission:accounting.assets.view|accounting.assets.depreciate|accounting.view');
                Route::post('/depreciation', [FixedAssetController::class, 'depreciationPost'])
                    ->middleware('tenant.permission:accounting.assets.depreciate');
                Route::get('/', [FixedAssetController::class, 'index'])
                    ->middleware('tenant.permission:accounting.assets.view|accounting.view');
                Route::post('/', [FixedAssetController::class, 'store'])
                    ->middleware('tenant.permission:accounting.assets.create');
                Route::get('/{asset}', [FixedAssetController::class, 'show'])
                    ->whereNumber('asset')
                    ->middleware('tenant.permission:accounting.assets.view|accounting.view');
                Route::put('/{asset}', [FixedAssetController::class, 'update'])
                    ->whereNumber('asset')
                    ->middleware('tenant.permission:accounting.assets.edit');
                Route::get('/{asset}/disposal-preview', [FixedAssetController::class, 'previewDisposal'])
                    ->whereNumber('asset')
                    ->middleware('tenant.permission:accounting.assets.dispose|accounting.assets.view');
                Route::post('/{asset}/dispose', [FixedAssetController::class, 'dispose'])
                    ->whereNumber('asset')
                    ->middleware('tenant.permission:accounting.assets.dispose');
                Route::post('/{asset}/transfer', [FixedAssetController::class, 'transfer'])
                    ->whereNumber('asset')
                    ->middleware('tenant.permission:accounting.assets.transfer');
            });

            Route::prefix('/equity')->group(function (): void {
                Route::get('/', [EquityController::class, 'index'])
                    ->middleware('tenant.permission:accounting.equity.view|accounting.view');
                Route::post('/', [EquityController::class, 'store'])
                    ->middleware('tenant.permission:accounting.equity.create');
            });

            Route::prefix('/liabilities')->group(function (): void {
                Route::get('/', [LiabilityController::class, 'index'])
                    ->middleware('tenant.permission:accounting.liabilities.view|accounting.view');
                Route::post('/', [LiabilityController::class, 'store'])
                    ->middleware('tenant.permission:accounting.liabilities.create');
                Route::get('/{liability}', [LiabilityController::class, 'show'])
                    ->whereNumber('liability')
                    ->middleware('tenant.permission:accounting.liabilities.view|accounting.view');
                Route::post('/{liability}/settle', [LiabilityController::class, 'settle'])
                    ->whereNumber('liability')
                    ->middleware('tenant.permission:accounting.liabilities.settle');
            });

            Route::prefix('/treasury/banks')->group(function (): void {
                Route::get('/', [BankAccountController::class, 'index'])
                    ->middleware('tenant.permission:accounting.reconciliation.view|accounting.view');
                Route::post('/', [BankAccountController::class, 'store'])
                    ->middleware('tenant.permission:accounting.reconciliation.create');
                Route::get('/{bank}', [BankAccountController::class, 'show'])
                    ->whereNumber('bank')
                    ->middleware('tenant.permission:accounting.reconciliation.view|accounting.view');
                Route::put('/{bank}', [BankAccountController::class, 'update'])
                    ->whereNumber('bank')
                    ->middleware('tenant.permission:accounting.reconciliation.create');
            });

            Route::prefix('/reconciliations')->group(function (): void {
                Route::get('/', [BankReconciliationController::class, 'index'])
                    ->middleware('tenant.permission:accounting.reconciliation.view|accounting.view');
                Route::post('/', [BankReconciliationController::class, 'store'])
                    ->middleware('tenant.permission:accounting.reconciliation.create');
                Route::get('/{reconciliation}', [BankReconciliationController::class, 'show'])
                    ->whereNumber('reconciliation')
                    ->middleware('tenant.permission:accounting.reconciliation.view|accounting.view');
                Route::post('/{reconciliation}/statements/preview', [BankReconciliationController::class, 'previewImport'])
                    ->whereNumber('reconciliation')
                    ->middleware(['tenant.permission:accounting.reconciliation.create', 'throttle:10,1']);
                Route::post('/{reconciliation}/statements', [BankReconciliationController::class, 'import'])
                    ->whereNumber('reconciliation')
                    ->middleware(['tenant.permission:accounting.reconciliation.create', 'throttle:10,1']);
                Route::post('/{reconciliation}/auto-match', [BankReconciliationController::class, 'autoMatch'])
                    ->whereNumber('reconciliation')
                    ->middleware('tenant.permission:accounting.reconciliation.match');
                Route::post('/{reconciliation}/match', [BankReconciliationController::class, 'match'])
                    ->whereNumber('reconciliation')
                    ->middleware('tenant.permission:accounting.reconciliation.match');
                Route::post('/{reconciliation}/adjustments', [BankReconciliationController::class, 'adjust'])
                    ->whereNumber('reconciliation')
                    ->middleware('tenant.permission:accounting.reconciliation.adjust');
                Route::post('/{reconciliation}/submit', [BankReconciliationController::class, 'submit'])
                    ->whereNumber('reconciliation')
                    ->middleware('tenant.permission:accounting.reconciliation.create');
                Route::post('/{reconciliation}/reconcile', [BankReconciliationController::class, 'reconcile'])
                    ->whereNumber('reconciliation')
                    ->middleware('tenant.permission:accounting.reconciliation.create');
                Route::post('/{reconciliation}/lock', [BankReconciliationController::class, 'lock'])
                    ->whereNumber('reconciliation')
                    ->middleware('tenant.permission:accounting.reconciliation.lock');
                Route::post('/{reconciliation}/reopen', [BankReconciliationController::class, 'reopen'])
                    ->whereNumber('reconciliation')
                    ->middleware('tenant.permission:accounting.reconciliation.lock');
            });

            Route::prefix('/receivables')->group(function (): void {
                Route::get('/export', [ReceivableController::class, 'export'])
                    ->middleware('tenant.permission:accounting.receivables.export|accounting.reports.export');
                Route::get('/', [ReceivableController::class, 'index'])
                    ->middleware('tenant.permission:accounting.receivables.view|accounting.view');
                Route::get('/{customer}', [ReceivableController::class, 'show'])
                    ->whereNumber('customer')
                    ->middleware('tenant.permission:accounting.receivables.view|accounting.view');
            });

            Route::prefix('/payables')->group(function (): void {
                Route::get('/export', [PayableController::class, 'export'])
                    ->middleware('tenant.permission:accounting.payables.export|accounting.reports.export');
                Route::get('/', [PayableController::class, 'index'])
                    ->middleware('tenant.permission:accounting.payables.view|accounting.view');
                Route::get('/{supplier}', [PayableController::class, 'show'])
                    ->whereNumber('supplier')
                    ->middleware('tenant.permission:accounting.payables.view|accounting.view');
            });
        });

        Route::get('/treasury/accounts', [AccountingController::class, 'treasuryAccounts'])
            ->middleware(['plan.feature:accounting.enabled', 'tenant.permission:accounting.view']);

        Route::prefix('/customers')->middleware('plan.feature:customers.enabled')->group(function (): void {
            Route::get('/export', [CustomerController::class, 'export'])
                ->middleware('tenant.permission:customers.export');
            Route::get('/stats', [CustomerController::class, 'stats'])
                ->middleware('tenant.permission:customers.view');
            Route::get('/', [CustomerController::class, 'index'])
                ->middleware('tenant.permission:customers.view');
            Route::post('/', [CustomerController::class, 'store'])
                ->middleware('tenant.permission:customers.create');
            Route::get('/{customer}', [CustomerController::class, 'show'])
                ->whereNumber('customer')
                ->middleware('tenant.permission:customers.view');
            Route::put('/{customer}', [CustomerController::class, 'update'])
                ->whereNumber('customer')
                ->middleware('tenant.permission:customers.update');
            Route::delete('/{customer}', [CustomerController::class, 'destroy'])
                ->whereNumber('customer')
                ->middleware('tenant.permission:customers.delete');
        });

        Route::prefix('/branches')->middleware('plan.feature:branches.enabled')->group(function (): void {
            Route::get('/export', [BranchController::class, 'export'])
                ->middleware('tenant.permission:branches.export');
            Route::get('/', [BranchController::class, 'index'])
                ->middleware('tenant.permission:branches.view');
            Route::post('/', [BranchController::class, 'store'])
                ->middleware('tenant.permission:branches.create');
            Route::get('/{branch}', [BranchController::class, 'show'])
                ->whereNumber('branch')
                ->middleware('tenant.permission:branches.view');
            Route::put('/{branch}', [BranchController::class, 'update'])
                ->whereNumber('branch')
                ->middleware('tenant.permission:branches.update');
            Route::delete('/{branch}', [BranchController::class, 'destroy'])
                ->whereNumber('branch')
                ->middleware('tenant.permission:branches.delete');
        });

        Route::prefix('/suppliers')->middleware('plan.feature:suppliers.enabled')->group(function (): void {
            Route::get('/export', [SupplierController::class, 'export'])
                ->middleware('tenant.permission:suppliers.export');
            Route::get('/', [SupplierController::class, 'index'])
                ->middleware('tenant.permission:suppliers.view');
            Route::post('/', [SupplierController::class, 'store'])
                ->middleware('tenant.permission:suppliers.create');
            Route::get('/{supplier}/account', [SupplierController::class, 'account'])
                ->whereNumber('supplier')
                ->middleware('tenant.permission:suppliers.view');
            Route::get('/{supplier}', [SupplierController::class, 'show'])
                ->whereNumber('supplier')
                ->middleware('tenant.permission:suppliers.view');
            Route::put('/{supplier}', [SupplierController::class, 'update'])
                ->whereNumber('supplier')
                ->middleware('tenant.permission:suppliers.update');
            Route::delete('/{supplier}', [SupplierController::class, 'destroy'])
                ->whereNumber('supplier')
                ->middleware('tenant.permission:suppliers.delete');
            Route::get('/{supplier}/payments', [SupplierPaymentController::class, 'indexForSupplier'])
                ->whereNumber('supplier')
                ->middleware(['tenant.permission:supplier_payments.view', 'plan.feature:supplier_payments.enabled']);
            Route::post('/{supplier}/payments', [SupplierPaymentController::class, 'storeForSupplier'])
                ->whereNumber('supplier')
                ->middleware(['tenant.permission:supplier_payments.create', 'plan.feature:supplier_payments.enabled']);
        });

        Route::prefix('/purchase-orders')->middleware('plan.feature:purchase_orders.enabled')->group(function (): void {
            Route::get('/export', [PurchaseOrderController::class, 'export'])
                ->middleware('tenant.permission:purchase_orders.export');
            Route::get('/', [PurchaseOrderController::class, 'index'])
                ->middleware('tenant.permission:purchase_orders.view');
            Route::post('/', [PurchaseOrderController::class, 'store'])
                ->middleware('tenant.permission:purchase_orders.create');
            Route::post('/{purchaseOrder}/receive', [PurchaseOrderController::class, 'receive'])
                ->whereNumber('purchaseOrder')
                ->middleware('tenant.permission:purchase_orders.receive');
            Route::post('/{purchaseOrder}/return', [PurchaseOrderController::class, 'returnOrder'])
                ->whereNumber('purchaseOrder')
                ->middleware('tenant.permission:purchase_orders.return');
            Route::get('/{purchaseOrder}', [PurchaseOrderController::class, 'show'])
                ->whereNumber('purchaseOrder')
                ->middleware('tenant.permission:purchase_orders.view');
            Route::put('/{purchaseOrder}', [PurchaseOrderController::class, 'update'])
                ->whereNumber('purchaseOrder')
                ->middleware('tenant.permission:purchase_orders.update');
            Route::delete('/{purchaseOrder}', [PurchaseOrderController::class, 'destroy'])
                ->whereNumber('purchaseOrder')
                ->middleware('tenant.permission:purchase_orders.delete');
            Route::get('/{purchaseOrder}/payments', [SupplierPaymentController::class, 'indexForPurchaseOrder'])
                ->whereNumber('purchaseOrder')
                ->middleware(['tenant.permission:supplier_payments.view', 'plan.feature:supplier_payments.enabled']);
        });

        Route::prefix('/expense-categories')->middleware('plan.feature:expenses.enabled')->group(function (): void {
            Route::get('/', [ExpenseCategoryController::class, 'index'])
                ->middleware('tenant.permission:expense_categories.view');
            Route::post('/', [ExpenseCategoryController::class, 'store'])
                ->middleware('tenant.permission:expense_categories.create');
            Route::get('/{expenseCategory}', [ExpenseCategoryController::class, 'show'])
                ->whereNumber('expenseCategory')
                ->middleware('tenant.permission:expense_categories.view');
            Route::put('/{expenseCategory}', [ExpenseCategoryController::class, 'update'])
                ->whereNumber('expenseCategory')
                ->middleware('tenant.permission:expense_categories.update');
            Route::delete('/{expenseCategory}', [ExpenseCategoryController::class, 'destroy'])
                ->whereNumber('expenseCategory')
                ->middleware('tenant.permission:expense_categories.delete');
        });

        Route::prefix('/expenses')->middleware('plan.feature:expenses.enabled')->group(function (): void {
            Route::get('/summary', [ExpenseController::class, 'summary'])
                ->middleware('tenant.permission:expenses.summary');
            Route::get('/export', [ExpenseController::class, 'export'])
                ->middleware('tenant.permission:expenses.export');
            Route::get('/', [ExpenseController::class, 'index'])
                ->middleware('tenant.permission:expenses.view');
            Route::post('/', [ExpenseController::class, 'store'])
                ->middleware('tenant.permission:expenses.create');
            Route::post('/{expense}/approve', [ExpenseController::class, 'approve'])
                ->whereNumber('expense')
                ->middleware('tenant.permission:expenses.approve');
            Route::post('/{expense}/cancel', [ExpenseController::class, 'cancel'])
                ->whereNumber('expense')
                ->middleware('tenant.permission:expenses.cancel');
            Route::post('/{expense}/pay', [ExpenseController::class, 'pay'])
                ->whereNumber('expense')
                ->middleware('tenant.permission:expenses.pay');
            Route::get('/{expense}', [ExpenseController::class, 'show'])
                ->whereNumber('expense')
                ->middleware('tenant.permission:expenses.view');
            Route::put('/{expense}', [ExpenseController::class, 'update'])
                ->whereNumber('expense')
                ->middleware('tenant.permission:expenses.update');
            Route::delete('/{expense}', [ExpenseController::class, 'destroy'])
                ->whereNumber('expense')
                ->middleware('tenant.permission:expenses.delete');
        });

        Route::prefix('/cash-movements')->middleware('plan.feature:cash_movements.enabled')->group(function (): void {
            Route::get('/', [CashMovementController::class, 'index'])
                ->middleware('tenant.permission:cash_movements.view');
            Route::post('/', [CashMovementController::class, 'store'])
                ->middleware('tenant.permission:cash_movements.create');
        });

        Route::prefix('/cashboxes')->middleware('plan.feature:cashboxes.enabled')->group(function (): void {
            Route::get('/export', [CashboxController::class, 'export'])
                ->middleware('tenant.permission:cashboxes.export');
            Route::get('/daily-summary', [CashboxController::class, 'dailySummary'])
                ->middleware('tenant.permission:cashboxes.view');
            Route::post('/receive', [CashboxController::class, 'receive'])
                ->middleware('tenant.permission:cash_movements.create|cashboxes.update');
            Route::post('/pay', [CashboxController::class, 'pay'])
                ->middleware('tenant.permission:cash_movements.create|cashboxes.update');
            Route::post('/transfer', [CashboxController::class, 'transfer'])
                ->middleware('tenant.permission:cash_movements.create|cashboxes.update');
            Route::prefix('/statement')->group(function (): void {
                Route::get('/branches', [TransactionStatementController::class, 'branches'])
                    ->middleware('tenant.permission:cashboxes.view');
                Route::get('/summary', [TransactionStatementController::class, 'summary'])
                    ->middleware('tenant.permission:cashboxes.view');
                Route::get('/ledger', [TransactionStatementController::class, 'ledger'])
                    ->middleware('tenant.permission:cashboxes.view');
                Route::get('/export', [TransactionStatementController::class, 'export'])
                    ->middleware('tenant.permission:cashboxes.export');
                Route::post('/close-period', [TransactionStatementController::class, 'closePeriod'])
                    ->middleware('tenant.permission:cash_movements.create');
            });
            Route::get('/', [CashboxController::class, 'index'])
                ->middleware('tenant.permission:cashboxes.view');
            Route::post('/', [CashboxController::class, 'store'])
                ->middleware('tenant.permission:cashboxes.create');
            Route::get('/{cashbox}', [CashboxController::class, 'show'])
                ->whereNumber('cashbox')
                ->middleware('tenant.permission:cashboxes.view');
            Route::put('/{cashbox}', [CashboxController::class, 'update'])
                ->whereNumber('cashbox')
                ->middleware('tenant.permission:cashboxes.update');
            Route::delete('/{cashbox}', [CashboxController::class, 'destroy'])
                ->whereNumber('cashbox')
                ->middleware('tenant.permission:cashboxes.delete');
            Route::get('/{cashbox}/transactions', [CashboxController::class, 'transactions'])
                ->whereNumber('cashbox')
                ->middleware('tenant.permission:cashboxes.view');
            Route::post('/{cashbox}/recalculate', [CashboxController::class, 'recalculate'])
                ->whereNumber('cashbox')
                ->middleware('tenant.permission:cashboxes.recalculate');
        });

        Route::prefix('/payments')->middleware('plan.feature:payments.enabled')->group(function (): void {
            Route::get('/export', [PaymentController::class, 'export'])
                ->middleware('tenant.permission:payments.export');
            Route::get('/', [PaymentController::class, 'index'])
                ->middleware('tenant.permission:payments.view');
            Route::get('/{payment}', [PaymentController::class, 'show'])
                ->whereNumber('payment')
                ->middleware('tenant.permission:payments.view');
            Route::post('/{payment}/pay', [PaymentController::class, 'pay'])
                ->whereNumber('payment')
                ->middleware('tenant.permission:payments.pay');
            Route::post('/{payment}/cancel', [PaymentController::class, 'cancel'])
                ->whereNumber('payment')
                ->middleware('tenant.permission:payments.cancel');
        });

        Route::prefix('/dress-categories')->middleware('plan.dress_category')->group(function (): void {
            Route::get('/', [DressCategoryController::class, 'index'])
                ->middleware('tenant.permission:dress_categories.view');
            Route::post('/', [DressCategoryController::class, 'store'])
                ->middleware('tenant.permission:dress_categories.create');
            Route::get('/{dressCategory}', [DressCategoryController::class, 'show'])
                ->whereNumber('dressCategory')
                ->middleware('tenant.permission:dress_categories.view');
            Route::put('/{dressCategory}', [DressCategoryController::class, 'update'])
                ->whereNumber('dressCategory')
                ->middleware('tenant.permission:dress_categories.update');
            Route::delete('/{dressCategory}', [DressCategoryController::class, 'destroy'])
                ->whereNumber('dressCategory')
                ->middleware('tenant.permission:dress_categories.delete');
        });

        Route::prefix('/dresses')->middleware('plan.feature:dresses.enabled')->group(function (): void {
            Route::get('/export', [DressController::class, 'export'])
                ->middleware('tenant.permission:dresses.export');
            Route::get('/available-for-date', [DressController::class, 'availableForDate'])
                ->middleware('tenant.permission:dresses.view');
            Route::get('/', [DressController::class, 'index'])
                ->middleware('tenant.permission:dresses.view');
            Route::post('/', [DressController::class, 'store'])
                ->middleware('tenant.permission:dresses.create');
            Route::get('/{dress}/order-history', [DressController::class, 'orderHistory'])
                ->whereNumber('dress')
                ->middleware('tenant.permission:dresses.view');
            Route::get('/{dress}/rental-report', [DressController::class, 'rentalReport'])
                ->whereNumber('dress')
                ->middleware('tenant.permission:dresses.report.view');
            Route::get('/{dress}/unavailable-days', [DressController::class, 'unavailableDays'])
                ->whereNumber('dress')
                ->middleware('tenant.permission:dresses.view');
            Route::get('/{dress}', [DressController::class, 'show'])
                ->whereNumber('dress')
                ->middleware('tenant.permission:dresses.view');
            Route::match(['put', 'post'], '/{dress}', [DressController::class, 'update'])
                ->whereNumber('dress')
                ->middleware('tenant.permission:dresses.update');
            Route::post('/{dress}/transfer', [DressController::class, 'transfer'])
                ->whereNumber('dress')
                ->middleware(['tenant.permission:dresses.update', 'plan.feature:inventory.enabled']);
            Route::delete('/{dress}', [DressController::class, 'destroy'])
                ->whereNumber('dress')
                ->middleware('tenant.permission:dresses.delete');
            Route::get('/{dress}/inventory-movements', [DressController::class, 'inventoryMovements'])
                ->whereNumber('dress')
                ->middleware(['tenant.permission:inventory.view', 'plan.feature:inventory.enabled']);
        });

        Route::prefix('/invoices')->middleware('plan.feature:invoices.enabled')->group(function (): void {
            Route::get('/export', [InvoiceController::class, 'export'])
                ->middleware('tenant.permission:invoices.export');
            Route::get('/', [InvoiceController::class, 'index'])
                ->middleware('tenant.permission:invoices.view');
            Route::post('/', [InvoiceController::class, 'store'])
                ->middleware('tenant.permission:invoices.create');
            Route::post('/{invoice}/cancel', [InvoiceController::class, 'cancel'])
                ->whereNumber('invoice')
                ->middleware('tenant.permission:invoices.cancel');
            Route::post('/{invoice}/whatsapp', [InvoiceController::class, 'sendWhatsApp'])
                ->whereNumber('invoice')
                ->middleware('tenant.permission:invoices.view');
            Route::get('/{invoice}', [InvoiceController::class, 'show'])
                ->whereNumber('invoice')
                ->middleware('tenant.permission:invoices.view');
            Route::put('/{invoice}', [InvoiceController::class, 'update'])
                ->whereNumber('invoice')
                ->middleware('tenant.permission:invoices.update');
            Route::delete('/{invoice}', [InvoiceController::class, 'destroy'])
                ->whereNumber('invoice')
                ->middleware('tenant.permission:invoices.delete');

            Route::get('/{invoice}/payments', [InvoiceController::class, 'payments'])
                ->whereNumber('invoice')
                ->middleware('tenant.permission:invoice_payments.view');
            Route::post('/{invoice}/payments', [InvoiceController::class, 'addPayment'])
                ->whereNumber('invoice')
                ->middleware('tenant.permission:invoice_payments.create');

            Route::post('/{invoice}/deliver', [InvoiceDeliveryController::class, 'deliver'])
                ->whereNumber('invoice')
                ->middleware(['tenant.permission:invoice_delivery.deliver', 'plan.feature:deliveries.enabled']);
            Route::post('/{invoice}/return', [InvoiceDeliveryController::class, 'returnInvoice'])
                ->whereNumber('invoice')
                ->middleware(['tenant.permission:invoice_delivery.return', 'plan.feature:returns.enabled']);
            Route::get('/{invoice}/delivery-records', [InvoiceDeliveryController::class, 'deliveryRecords'])
                ->whereNumber('invoice')
                ->middleware(['tenant.permission:invoice_delivery.view', 'plan.feature:deliveries.enabled']);

            Route::post('/{invoice}/security-deposit/deductions', [InvoiceDeliveryController::class, 'addSecurityDepositDeduction'])
                ->whereNumber('invoice')
                ->middleware('tenant.permission:security_deposit.deduct');
            Route::get('/{invoice}/security-deposit/transactions', [InvoiceDeliveryController::class, 'securityDepositTransactions'])
                ->whereNumber('invoice')
                ->middleware('tenant.permission:security_deposit.view');
        });

        Route::prefix('/hr')->middleware('plan.feature:hr.enabled')->group(function (): void {
            Route::get('/dashboard', [HrDashboardController::class, 'index'])
                ->middleware('tenant.permission:hr.dashboard.view');
            Route::get('/activity-logs', [EmployeeActivityController::class, 'index'])
                ->middleware('tenant.permission:hr.activity.view');

            Route::get('/departments', [HrDepartmentController::class, 'index'])
                ->middleware('tenant.permission:hr.departments.view');
            Route::post('/departments', [HrDepartmentController::class, 'store'])
                ->middleware('tenant.permission:hr.departments.create');
            Route::get('/departments/{department}', [HrDepartmentController::class, 'show'])
                ->whereNumber('department')
                ->middleware('tenant.permission:hr.departments.view');
            Route::put('/departments/{department}', [HrDepartmentController::class, 'update'])
                ->whereNumber('department')
                ->middleware('tenant.permission:hr.departments.update');
            Route::delete('/departments/{department}', [HrDepartmentController::class, 'destroy'])
                ->whereNumber('department')
                ->middleware('tenant.permission:hr.departments.delete');

            Route::get('/job-titles', [HrJobTitleController::class, 'index'])
                ->middleware('tenant.permission:hr.job_titles.view');
            Route::post('/job-titles', [HrJobTitleController::class, 'store'])
                ->middleware('tenant.permission:hr.job_titles.create');
            Route::get('/job-titles/{jobTitle}', [HrJobTitleController::class, 'show'])
                ->whereNumber('jobTitle')
                ->middleware('tenant.permission:hr.job_titles.view');
            Route::put('/job-titles/{jobTitle}', [HrJobTitleController::class, 'update'])
                ->whereNumber('jobTitle')
                ->middleware('tenant.permission:hr.job_titles.update');
            Route::delete('/job-titles/{jobTitle}', [HrJobTitleController::class, 'destroy'])
                ->whereNumber('jobTitle')
                ->middleware('tenant.permission:hr.job_titles.delete');

            Route::get('/access/roles', [HrAccessController::class, 'roles'])
                ->middleware('tenant.permission:hr.employees.view');
            Route::get('/access/permissions', [HrAccessController::class, 'permissions'])
                ->middleware('tenant.permission:hr.employees.view');

            Route::get('/employees', [HrEmployeeController::class, 'index'])
                ->middleware('tenant.permission:hr.employees.view');
            Route::post('/employees', [HrEmployeeController::class, 'store'])
                ->middleware('tenant.permission:hr.employees.create');
            Route::patch('/employees/{employee}/status', [HrEmployeeController::class, 'updateStatus'])
                ->whereNumber('employee')
                ->middleware('tenant.permission:hr.employees.status');
            Route::get('/employees/{employee}/summary', [HrEmployeeController::class, 'summary'])
                ->whereNumber('employee')
                ->middleware('tenant.permission:hr.employees.view');
            Route::get('/employees/{employee}/documents', [HrEmployeeController::class, 'documents'])
                ->whereNumber('employee')
                ->middleware('tenant.permission:hr.documents.view');
            Route::get('/employees/{employee}/notes', [HrEmployeeNoteController::class, 'index'])
                ->whereNumber('employee')
                ->middleware('tenant.permission:hr.employees.view');
            Route::post('/employees/{employee}/notes', [HrEmployeeNoteController::class, 'store'])
                ->whereNumber('employee')
                ->middleware('tenant.permission:hr.employees.update');
            Route::delete('/employees/{employee}/notes/{note}', [HrEmployeeNoteController::class, 'destroy'])
                ->whereNumber('employee')
                ->whereNumber('note')
                ->middleware('tenant.permission:hr.employees.update');
            Route::get('/employees/{employee}', [HrEmployeeController::class, 'show'])
                ->whereNumber('employee')
                ->middleware('tenant.permission:hr.employees.view');
            Route::put('/employees/{employee}', [HrEmployeeController::class, 'update'])
                ->whereNumber('employee')
                ->middleware('tenant.permission:hr.employees.update');
            Route::delete('/employees/{employee}', [HrEmployeeController::class, 'destroy'])
                ->whereNumber('employee')
                ->middleware('tenant.permission:hr.employees.delete');

            Route::get('/documents/expiry-alerts', [HrDocumentController::class, 'expiryAlerts'])
                ->middleware('tenant.permission:hr.documents.view');
            Route::get('/documents', [HrDocumentController::class, 'index'])
                ->middleware('tenant.permission:hr.documents.view');
            Route::post('/documents', [HrDocumentController::class, 'store'])
                ->middleware('tenant.permission:hr.documents.upload');
            Route::get('/documents/{document}', [HrDocumentController::class, 'show'])
                ->whereNumber('document')
                ->middleware('tenant.permission:hr.documents.view');
            Route::put('/documents/{document}', [HrDocumentController::class, 'update'])
                ->whereNumber('document')
                ->middleware('tenant.permission:hr.documents.upload');
            Route::delete('/documents/{document}', [HrDocumentController::class, 'destroy'])
                ->whereNumber('document')
                ->middleware('tenant.permission:hr.documents.delete');

            Route::get('/settings', [HrSettingController::class, 'index'])
                ->middleware('tenant.permission:hr.settings.view');
            Route::put('/settings', [HrSettingController::class, 'update'])
                ->middleware('tenant.permission:hr.settings.update');

            Route::get('/shifts', [HrShiftController::class, 'index'])
                ->middleware('tenant.permission:hr.shifts.view');
            Route::post('/shifts', [HrShiftController::class, 'store'])
                ->middleware('tenant.permission:hr.shifts.create');
            Route::get('/shifts/{shift}', [HrShiftController::class, 'show'])
                ->whereNumber('shift')
                ->middleware('tenant.permission:hr.shifts.view');
            Route::put('/shifts/{shift}', [HrShiftController::class, 'update'])
                ->whereNumber('shift')
                ->middleware('tenant.permission:hr.shifts.update');
            Route::delete('/shifts/{shift}', [HrShiftController::class, 'destroy'])
                ->whereNumber('shift')
                ->middleware('tenant.permission:hr.shifts.delete');

            Route::get('/attendance', [HrAttendanceController::class, 'index'])
                ->middleware('tenant.permission:hr.attendance.view');
            Route::post('/attendance', [HrAttendanceController::class, 'store'])
                ->middleware('tenant.permission:hr.attendance.create');
            Route::put('/attendance/{attendance}', [HrAttendanceController::class, 'update'])
                ->whereNumber('attendance')
                ->middleware('tenant.permission:hr.attendance.update');

            Route::get('/leaves', [HrLeaveController::class, 'index'])
                ->middleware('tenant.permission:hr.leaves.view');
            Route::post('/leaves', [HrLeaveController::class, 'store'])
                ->middleware('tenant.permission:hr.leaves.create');
            Route::patch('/leaves/{leave}/status', [HrLeaveController::class, 'updateStatus'])
                ->whereNumber('leave')
                ->middleware('tenant.permission:hr.leaves.status');

            Route::get('/payroll', [HrPayrollController::class, 'index'])
                ->middleware('tenant.permission:hr.view');
            Route::get('/payroll/history', [HrPayrollController::class, 'history'])
                ->middleware('tenant.permission:hr.view');
            Route::get('/payroll/employees/{employee}/payslip', [HrPayrollController::class, 'payslip'])
                ->whereNumber('employee')
                ->middleware('tenant.permission:hr.view');
            Route::get('/payroll/employees/{employee}/history', [HrPayrollController::class, 'employeeHistory'])
                ->whereNumber('employee')
                ->middleware('tenant.permission:hr.view');
            Route::post('/payroll/pay', [HrPayrollController::class, 'pay'])
                ->middleware('tenant.permission:hr.view');
            Route::get('/payroll/adjustments', [HrPayrollAdjustmentController::class, 'index'])
                ->middleware('tenant.permission:hr.view');
            Route::post('/payroll/adjustments', [HrPayrollAdjustmentController::class, 'store'])
                ->middleware('tenant.permission:hr.view');
            Route::delete('/payroll/adjustments/{adjustment}', [HrPayrollAdjustmentController::class, 'destroy'])
                ->whereNumber('adjustment')
                ->middleware('tenant.permission:hr.view');
        });

        Route::prefix('/intelligence')->middleware(['plan.feature:ai_assistant.enabled'])->group(function (): void {
            Route::get('/health', [IntelligenceController::class, 'health'])
                ->middleware('tenant.permission:intelligence.view');

            Route::get('/snapshot', [IntelligenceInsightsController::class, 'snapshot'])
                ->middleware('tenant.permission:intelligence.view');
            Route::get('/business-health', [IntelligenceInsightsController::class, 'health'])
                ->middleware('tenant.permission:intelligence.view');
            Route::get('/daily-brief', [IntelligenceInsightsController::class, 'dailyBrief'])
                ->middleware('tenant.permission:intelligence.view');

            Route::get('/conversations', [IntelligenceController::class, 'conversations'])
                ->middleware('tenant.permission:intelligence.chat');
            Route::post('/conversations', [IntelligenceController::class, 'storeConversation'])
                ->middleware('tenant.permission:intelligence.chat');
            Route::get('/conversations/{conversation}', [IntelligenceController::class, 'showConversation'])
                ->whereNumber('conversation')
                ->middleware('tenant.permission:intelligence.chat');
            Route::delete('/conversations/{conversation}', [IntelligenceController::class, 'archiveConversation'])
                ->whereNumber('conversation')
                ->middleware('tenant.permission:intelligence.chat');
            Route::post('/conversations/{conversation}/messages', [IntelligenceController::class, 'storeMessage'])
                ->whereNumber('conversation')
                ->middleware('tenant.permission:intelligence.chat');

            Route::get('/runs/{run}', [IntelligenceController::class, 'showRun'])
                ->whereNumber('run')
                ->middleware('tenant.permission:intelligence.chat');
        });

        Route::prefix('/website')->middleware('plan.feature:website.enabled')->group(function (): void {
            Route::get('/overview', [WebsiteController::class, 'overview'])
                ->middleware('tenant.permission:website.view');

            Route::get('/templates', [WebsiteController::class, 'templates'])
                ->middleware('tenant.permission:website.templates|website.view');
            Route::post('/templates/{key}/apply', [WebsiteController::class, 'applyTemplate'])
                ->middleware('tenant.permission:website.templates|website.manage');

            Route::get('/branding', [WebsiteController::class, 'branding'])
                ->middleware('tenant.permission:website.design|website.view');
            Route::put('/branding', [WebsiteController::class, 'updateBranding'])
                ->middleware('tenant.permission:website.design|website.manage');
            Route::post('/branding', [WebsiteController::class, 'updateBranding'])
                ->middleware('tenant.permission:website.design|website.manage');

            Route::get('/pages', [WebsiteController::class, 'pages'])
                ->middleware('tenant.permission:website.pages|website.view');
            Route::post('/pages/bootstrap', [WebsiteController::class, 'bootstrapPages'])
                ->middleware('tenant.permission:website.pages|website.manage');
            Route::post('/pages', [WebsiteController::class, 'storePage'])
                ->middleware('tenant.permission:website.pages|website.manage');
            Route::put('/pages/{page}', [WebsiteController::class, 'updatePage'])
                ->whereNumber('page')
                ->middleware('tenant.permission:website.pages|website.manage');
            Route::delete('/pages/{page}', [WebsiteController::class, 'destroyPage'])
                ->whereNumber('page')
                ->middleware('tenant.permission:website.pages|website.manage');

            Route::get('/sections', [WebsiteController::class, 'sections'])
                ->middleware('tenant.permission:website.sections|website.view');
            Route::put('/sections', [WebsiteController::class, 'syncSections'])
                ->middleware('tenant.permission:website.sections|website.manage');

            Route::get('/menus', [WebsiteController::class, 'menus'])
                ->middleware('tenant.permission:website.design|website.view');
            Route::post('/menus', [WebsiteController::class, 'storeMenu'])
                ->middleware('tenant.permission:website.design|website.manage');
            Route::put('/menus/{menu}', [WebsiteController::class, 'updateMenu'])
                ->whereNumber('menu')
                ->middleware('tenant.permission:website.design|website.manage');
            Route::delete('/menus/{menu}', [WebsiteController::class, 'destroyMenu'])
                ->whereNumber('menu')
                ->middleware('tenant.permission:website.design|website.manage');

            Route::get('/preview', [WebsiteController::class, 'preview'])
                ->middleware('tenant.permission:website.view');

            Route::get('/content/texts', [WebsiteController::class, 'texts'])
                ->middleware('tenant.permission:website.content|website.view');
            Route::put('/content/texts', [WebsiteController::class, 'updateTexts'])
                ->middleware('tenant.permission:website.content|website.manage');

            Route::get('/content/media', [WebsiteController::class, 'media'])
                ->middleware('tenant.permission:website.media|website.view');
            Route::post('/content/media', [WebsiteController::class, 'storeMedia'])
                ->middleware('tenant.permission:website.media|website.manage');
            Route::delete('/content/media/{medium}', [WebsiteController::class, 'destroyMedia'])
                ->whereNumber('medium')
                ->middleware('tenant.permission:website.media|website.manage');

            Route::get('/content/gallery', [WebsiteController::class, 'gallery'])
                ->middleware('tenant.permission:website.media|website.view');
            Route::post('/content/gallery', [WebsiteController::class, 'storeAlbum'])
                ->middleware('tenant.permission:website.media|website.manage');
            Route::put('/content/gallery/{album}', [WebsiteController::class, 'updateAlbum'])
                ->whereNumber('album')
                ->middleware('tenant.permission:website.media|website.manage');
            Route::delete('/content/gallery/{album}', [WebsiteController::class, 'destroyAlbum'])
                ->whereNumber('album')
                ->middleware('tenant.permission:website.media|website.manage');
            Route::get('/content/gallery/{album}/images', [WebsiteController::class, 'albumImages'])
                ->whereNumber('album')
                ->middleware('tenant.permission:website.media|website.view');
            Route::post('/content/gallery/{album}/images', [WebsiteController::class, 'storeAlbumImage'])
                ->whereNumber('album')
                ->middleware('tenant.permission:website.media|website.manage');
            Route::delete('/content/gallery/images/{image}', [WebsiteController::class, 'destroyGalleryImage'])
                ->whereNumber('image')
                ->middleware('tenant.permission:website.media|website.manage');

            Route::get('/content/dresses', [WebsiteController::class, 'dresses'])
                ->middleware('tenant.permission:website.products|website.view');
            Route::get('/content/products', [WebsiteController::class, 'products'])
                ->middleware('tenant.permission:website.products|website.view');
            Route::post('/content/products', [WebsiteController::class, 'upsertProduct'])
                ->middleware('tenant.permission:website.products|website.manage');

            Route::get('/content/services', [WebsiteController::class, 'services'])
                ->middleware('tenant.permission:website.services|website.view');
            Route::post('/content/services', [WebsiteController::class, 'storeService'])
                ->middleware('tenant.permission:website.services|website.manage');
            Route::put('/content/services/{service}', [WebsiteController::class, 'updateService'])
                ->whereNumber('service')
                ->middleware('tenant.permission:website.services|website.manage');
            Route::delete('/content/services/{service}', [WebsiteController::class, 'destroyService'])
                ->whereNumber('service')
                ->middleware('tenant.permission:website.services|website.manage');

            Route::get('/bookings/settings', [WebsiteController::class, 'bookingSettings'])
                ->middleware('tenant.permission:website.bookings|website.view');
            Route::put('/bookings/settings', [WebsiteController::class, 'updateBookingSettings'])
                ->middleware('tenant.permission:website.bookings|website.manage');
            Route::get('/bookings/fittings', [WebsiteController::class, 'fittings'])
                ->middleware('tenant.permission:website.bookings|website.view');
            Route::get('/bookings/appointments', [WebsiteController::class, 'appointments'])
                ->middleware('tenant.permission:website.bookings|website.view');
            Route::get('/bookings/orders', [WebsiteController::class, 'orders'])
                ->middleware('tenant.permission:website.orders|website.view');
            Route::put('/bookings/requests/{request}', [WebsiteController::class, 'updateBookingRequest'])
                ->whereNumber('request')
                ->middleware('tenant.permission:website.bookings|website.manage');

            Route::get('/leads', [WebsiteController::class, 'leads'])
                ->middleware('tenant.permission:website.leads|website.view');
            Route::put('/leads/{lead}', [WebsiteController::class, 'updateLead'])
                ->whereNumber('lead')
                ->middleware('tenant.permission:website.leads|website.manage');
            Route::get('/leads/customers', [WebsiteController::class, 'leadCustomers'])
                ->middleware('tenant.permission:website.leads|website.view');
            Route::get('/leads/forms', [WebsiteController::class, 'forms'])
                ->middleware('tenant.permission:website.leads|website.view');
            Route::put('/leads/forms/{form}', [WebsiteController::class, 'updateForm'])
                ->whereNumber('form')
                ->middleware('tenant.permission:website.leads|website.manage');
            Route::get('/leads/sources', [WebsiteController::class, 'leadSources'])
                ->middleware('tenant.permission:website.leads|website.view');

            Route::get('/communication/messages', [WebsiteController::class, 'messages'])
                ->middleware('tenant.permission:website.messages|website.view');
            Route::put('/communication/messages/{message}', [WebsiteController::class, 'updateMessage'])
                ->whereNumber('message')
                ->middleware('tenant.permission:website.messages|website.manage');
            Route::get('/communication/channels', [WebsiteController::class, 'channels'])
                ->middleware('tenant.permission:website.settings|website.view');
            Route::put('/communication/channels', [WebsiteController::class, 'updateChannels'])
                ->middleware('tenant.permission:website.settings|website.manage');

            Route::get('/marketing/seo', [WebsiteController::class, 'seo'])
                ->middleware('tenant.permission:website.seo|website.view');
            Route::put('/marketing/seo', [WebsiteController::class, 'updateSeo'])
                ->middleware('tenant.permission:website.seo|website.manage');
            Route::get('/marketing/analytics', [WebsiteController::class, 'analytics'])
                ->middleware('tenant.permission:website.analytics|website.view');
            Route::get('/marketing/pixels', [WebsiteController::class, 'pixels'])
                ->middleware('tenant.permission:website.marketing|website.view');
            Route::put('/marketing/pixels', [WebsiteController::class, 'updatePixels'])
                ->middleware('tenant.permission:website.marketing|website.manage');
            Route::get('/marketing/sharing', [WebsiteController::class, 'sharing'])
                ->middleware('tenant.permission:website.marketing|website.view');
            Route::put('/marketing/sharing', [WebsiteController::class, 'updateSharing'])
                ->middleware('tenant.permission:website.marketing|website.manage');

            Route::get('/domain', [WebsiteController::class, 'domain'])
                ->middleware('tenant.permission:website.domain|website.view');
            Route::get('/domain/check-username', [WebsiteController::class, 'checkUsername'])
                ->middleware('tenant.permission:website.domain|website.view');
            Route::put('/domain', [WebsiteController::class, 'updateDomain'])
                ->middleware('tenant.permission:website.domain|website.manage');
            Route::post('/domain/verify', [WebsiteController::class, 'verifyDomain'])
                ->middleware('tenant.permission:website.domain|website.manage');

            Route::get('/settings', [WebsiteController::class, 'settings'])
                ->middleware('tenant.permission:website.settings|website.view');
            Route::put('/settings', [WebsiteController::class, 'updateSettings'])
                ->middleware('tenant.permission:website.settings|website.manage');
            Route::get('/settings/notifications', [WebsiteController::class, 'notifications'])
                ->middleware('tenant.permission:website.settings|website.view');
            Route::put('/settings/notifications', [WebsiteController::class, 'updateNotifications'])
                ->middleware('tenant.permission:website.settings|website.manage');
            Route::get('/settings/permissions', [WebsiteController::class, 'permissions'])
                ->middleware('tenant.permission:website.settings|website.view');
            Route::get('/settings/status', [WebsiteController::class, 'status'])
                ->middleware('tenant.permission:website.publish|website.view');
            Route::put('/settings/status', [WebsiteController::class, 'updateStatus'])
                ->middleware('tenant.permission:website.publish|website.manage');
        });

        Route::prefix('/marketplace')->middleware('plan.feature:marketplace.enabled')->group(function (): void {
            Route::get('/meta', [MarketplaceController::class, 'meta'])
                ->middleware('tenant.permission:marketplace.view');
            Route::get('/overview', [MarketplaceController::class, 'overview'])
                ->middleware('tenant.permission:marketplace.view');

            Route::get('/products', [MarketplaceController::class, 'products'])
                ->middleware('tenant.permission:marketplace.products');
            Route::post('/products', [MarketplaceController::class, 'storeProduct'])
                ->middleware('tenant.permission:marketplace.products');
            Route::get('/products/{product}', [MarketplaceController::class, 'showProduct'])
                ->whereNumber('product')
                ->middleware('tenant.permission:marketplace.products');
            Route::put('/products/{product}', [MarketplaceController::class, 'updateProduct'])
                ->whereNumber('product')
                ->middleware('tenant.permission:marketplace.products');
            Route::post('/products/{product}/publish', [MarketplaceController::class, 'publishProduct'])
                ->whereNumber('product')
                ->middleware('tenant.permission:marketplace.products');

            Route::get('/orders', [MarketplaceController::class, 'orders'])
                ->middleware('tenant.permission:marketplace.orders');
            Route::get('/orders/{order}', [MarketplaceController::class, 'showOrder'])
                ->middleware('tenant.permission:marketplace.orders');
            Route::put('/orders/{order}/status', [MarketplaceController::class, 'updateOrderStatus'])
                ->middleware('tenant.permission:marketplace.orders');

            Route::get('/customers', [MarketplaceController::class, 'customers'])
                ->middleware('tenant.permission:marketplace.customers');
            Route::get('/sales', [MarketplaceController::class, 'sales'])
                ->middleware('tenant.permission:marketplace.sales');

            Route::get('/offers', [MarketplaceController::class, 'offers'])
                ->middleware('tenant.permission:marketplace.offers');
            Route::post('/offers', [MarketplaceController::class, 'storeOffer'])
                ->middleware('tenant.permission:marketplace.offers');
            Route::put('/offers/{offer}', [MarketplaceController::class, 'updateOffer'])
                ->whereNumber('offer')
                ->middleware('tenant.permission:marketplace.offers');
            Route::post('/offers/{offer}/stop', [MarketplaceController::class, 'stopOffer'])
                ->whereNumber('offer')
                ->middleware('tenant.permission:marketplace.offers');

            Route::get('/reviews', [MarketplaceController::class, 'reviews'])
                ->middleware('tenant.permission:marketplace.reviews');
            Route::post('/reviews/{review}/reply', [MarketplaceController::class, 'replyReview'])
                ->whereNumber('review')
                ->middleware('tenant.permission:marketplace.reviews');
            Route::post('/reviews/{review}/hide', [MarketplaceController::class, 'hideReview'])
                ->whereNumber('review')
                ->middleware('tenant.permission:marketplace.reviews');

            Route::get('/threads', [MarketplaceController::class, 'threads'])
                ->middleware('tenant.permission:marketplace.messages');
            Route::get('/threads/{thread}', [MarketplaceController::class, 'showThread'])
                ->whereNumber('thread')
                ->middleware('tenant.permission:marketplace.messages');
            Route::post('/threads/{thread}/reply', [MarketplaceController::class, 'replyThread'])
                ->whereNumber('thread')
                ->middleware('tenant.permission:marketplace.messages');
            Route::post('/threads/{thread}/close', [MarketplaceController::class, 'closeThread'])
                ->whereNumber('thread')
                ->middleware('tenant.permission:marketplace.messages');

            Route::get('/fittings', [MarketplaceController::class, 'fittings'])
                ->middleware('tenant.permission:marketplace.bookings');
            Route::post('/fittings', [MarketplaceController::class, 'storeFitting'])
                ->middleware('tenant.permission:marketplace.bookings');
            Route::put('/fittings/{fitting}/status', [MarketplaceController::class, 'updateFittingStatus'])
                ->whereNumber('fitting')
                ->middleware('tenant.permission:marketplace.bookings');

            Route::get('/store', [MarketplaceController::class, 'storeSettings'])
                ->middleware('tenant.permission:marketplace.settings');
            Route::put('/store', [MarketplaceController::class, 'updateStoreSettings'])
                ->middleware('tenant.permission:marketplace.settings|marketplace.publish');

            Route::get('/website', [MarketplaceController::class, 'websiteSettings'])
                ->middleware('tenant.permission:marketplace.website');
            Route::put('/website', [MarketplaceController::class, 'updateWebsiteSettings'])
                ->middleware('tenant.permission:marketplace.website');
            Route::get('/website/preview', [MarketplaceController::class, 'websitePreview'])
                ->middleware('tenant.permission:marketplace.website');
        });

        // Sprint 18A — AI Assistant product surface (shell UI; no Planner/Gateway)
        require base_path('packages/dressnmore-platform/routes/tenant-ai.php');
        require base_path('packages/smart-assistant-product/routes/tenant-smart-assistant.php');
    });
});
