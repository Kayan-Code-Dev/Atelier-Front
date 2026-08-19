<?php

namespace App\Services\Tenant;

use App\Accounting\AccountBalanceService;
use App\Accounting\AccountingMoney;
use App\Models\Tenant\Account;
use App\Models\Tenant\Cashbox;
use App\Models\Tenant\FixedAsset;
use App\Models\Tenant\FixedAssetDepreciationEntry;
use App\Support\ReportDateRange;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class FinancialStatementService
{
    /** @var list<string> */
    public const CASH_CODES = ['1000', '1010'];

    /** @var list<string> */
    public const RECEIVABLE_CODES = ['1200'];

    /** @var list<string> */
    public const INVENTORY_CODES = ['1300'];

    /** @var list<string> */
    public const FIXED_ASSET_CODES = ['1410', '1420', '1430', '1440', '1450', '1460'];

    /** @var list<string> */
    public const ACCUM_DEP_CODES = ['1490'];

    /** @var list<string> */
    public const PAYABLE_CODES = ['2000'];

    /** @var list<string> */
    public const LOAN_CODES = ['2200'];

    /** @var list<string> */
    public const OTHER_LIABILITY_CODES = ['2100', '2290'];

    /** @var list<string> */
    public const CAPITAL_CODES = ['3000'];

    /** @var list<string> */
    public const DRAWINGS_CODES = ['3100'];

    /** @var list<string> */
    public const RETURN_CODES = ['4900', '4910'];

    /** @var list<string> */
    public const COGS_CODES = ['5400', '5410'];

    /** @var list<string> */
    public const OPERATING_CODES = ['5000', '5500'];

    /** @var list<string> */
    public const RENT_CODES = ['5100'];

    /** @var list<string> */
    public const SALARY_CODES = ['5200'];

    /** @var list<string> */
    public const UTILITY_CODES = ['5110'];

    /** @var list<string> */
    public const MARKETING_CODES = ['5120'];

    /** @var list<string> */
    public const DEPRECIATION_CODES = ['5300'];

    public function __construct(private readonly AccountBalanceService $balances) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function incomeStatement(array $filters): array
    {
        $period = ReportDateRange::resolve($filters);
        $statement = $this->buildIncomeStatement($period['from'], $period['to'], $this->branchId($filters));

        if ($this->wantsCompare($filters)) {
            $previous = $this->compareRange($filters, $period);
            $compare = $this->buildIncomeStatement($previous['from'], $previous['to'], $this->branchId($filters));
            $statement['compare'] = $compare;
            $statement['variance'] = $this->incomeVariance($statement, $compare);
        }

        return $statement;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function balanceSheet(array $filters): array
    {
        $asOf = $this->asOf($filters);
        $sheet = $this->buildBalanceSheet($asOf, $this->branchId($filters));

        if ($this->wantsCompare($filters)) {
            $compareAsOf = trim((string) ($filters['compare_date'] ?? ''));
            if ($compareAsOf === '') {
                $compareAsOf = CarbonImmutable::parse($asOf)->subMonthNoOverflow()->endOfMonth()->toDateString();
            }
            $compare = $this->buildBalanceSheet($compareAsOf, $this->branchId($filters));
            $sheet['compare'] = $compare;
            $sheet['variance'] = $this->balanceVariance($sheet, $compare);
        }

        return $sheet;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function trialBalance(array $filters): array
    {
        $period = ReportDateRange::resolve($filters);
        $branchId = $this->branchId($filters);
        $includeZeros = filter_var($filters['include_zeros'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $rows = $this->balances->trialBalance($period['from'], $period['to'], $branchId);

        $lines = [];
        $totalDebit = AccountingMoney::zero();
        $totalCredit = AccountingMoney::zero();

        foreach ($rows as $row) {
            $debit = $this->tbDebit($row);
            $credit = $this->tbCredit($row);
            if (! $includeZeros && AccountingMoney::isZero($debit) && AccountingMoney::isZero($credit)) {
                continue;
            }

            $lines[] = [
                'id' => $row['id'],
                'account_id' => $row['id'],
                'code' => $row['code'],
                'name' => $row['name'],
                'type' => $row['type'],
                'opening_balance' => $row['opening_balance'],
                'period_debit' => $row['debit'],
                'period_credit' => $row['credit'],
                'debit' => AccountingMoney::toFloat($debit),
                'credit' => AccountingMoney::toFloat($credit),
                'closing_balance' => $row['closing_balance'],
                'drill' => $this->accountDrill($row['id'], $period['from'], $period['to']),
            ];
            $totalDebit = AccountingMoney::add($totalDebit, $debit);
            $totalCredit = AccountingMoney::add($totalCredit, $credit);
        }

        $difference = AccountingMoney::toFloat(AccountingMoney::sub($totalDebit, $totalCredit));

        return [
            'period' => $period,
            'include_zeros' => $includeZeros,
            'lines' => $lines,
            'total_debit' => AccountingMoney::toFloat($totalDebit),
            'total_credit' => AccountingMoney::toFloat($totalCredit),
            'difference' => $difference,
            'balanced' => AccountingMoney::isZero($difference),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function cashFlow(array $filters): array
    {
        $period = ReportDateRange::resolve($filters);
        $branchId = $this->branchId($filters);
        $openingDate = CarbonImmutable::parse($period['from'])->subDay()->toDateString();

        $openingCash = $this->balances->sumCodes(self::CASH_CODES, $openingDate, $branchId);
        $closingCash = $this->balances->sumCodes(self::CASH_CODES, $period['to'], $branchId);

        $operating = $this->cashFlowSection([
            ['key' => 'sales', 'label' => 'المبيعات / الإيرادات', 'codes' => $this->revenueCodes()],
            ['key' => 'expenses', 'label' => 'المصروفات (بدون الإهلاك)', 'codes' => $this->cashExpenseCodes(), 'invert' => true],
            ['key' => 'receivables', 'label' => 'التغير في العملاء', 'codes' => self::RECEIVABLE_CODES, 'working_capital' => true],
            ['key' => 'inventory', 'label' => 'التغير في المخزون', 'codes' => self::INVENTORY_CODES, 'working_capital' => true],
            ['key' => 'payables', 'label' => 'التغير في الموردين', 'codes' => self::PAYABLE_CODES, 'working_capital' => true, 'liability' => true],
        ], $period['from'], $period['to'], $branchId);

        $investing = $this->cashFlowSection([
            ['key' => 'fixed_assets', 'label' => 'شراء/بيع الأصول الثابتة', 'codes' => self::FIXED_ASSET_CODES, 'working_capital' => true],
        ], $period['from'], $period['to'], $branchId);

        $financing = $this->cashFlowSection([
            ['key' => 'capital', 'label' => 'رأس المال', 'codes' => self::CAPITAL_CODES, 'liability' => true],
            ['key' => 'drawings', 'label' => 'المسحوبات', 'codes' => self::DRAWINGS_CODES, 'working_capital' => true],
            ['key' => 'loans', 'label' => 'القروض', 'codes' => self::LOAN_CODES, 'liability' => true],
        ], $period['from'], $period['to'], $branchId);

        $netMovement = AccountingMoney::toFloat(AccountingMoney::add(
            AccountingMoney::add($operating['total'], $investing['total']),
            $financing['total']
        ));
        $impliedClosing = AccountingMoney::toFloat(AccountingMoney::add($openingCash, $netMovement));
        $cashBridge = AccountingMoney::toFloat(AccountingMoney::sub($closingCash, $openingCash));

        $treasury = $this->treasuryReconciliation($closingCash, $branchId);

        return [
            'period' => $period,
            'operating' => $operating,
            'investing' => $investing,
            'financing' => $financing,
            'opening_cash' => $openingCash,
            'net_cash_movement' => $netMovement,
            'implied_closing_cash' => $impliedClosing,
            'closing_cash' => $closingCash,
            'gl_cash_movement' => $cashBridge,
            'reconciled_to_gl_cash' => AccountingMoney::isZero(AccountingMoney::sub($impliedClosing, $closingCash)),
            'treasury' => $treasury,
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function treasuryReconciliationCheck(array $filters = []): array
    {
        $asOf = $this->asOf($filters);
        $glCash = $this->balances->sumCodes(self::CASH_CODES, $asOf, $this->branchId($filters));

        return $this->treasuryReconciliation($glCash, $this->branchId($filters));
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function assetsReconciliationCheck(array $filters = []): array
    {
        $asOf = $this->asOf($filters);
        $branchId = $this->branchId($filters);
        $glGross = $this->balances->sumCodes(self::FIXED_ASSET_CODES, $asOf, $branchId);
        $glAccum = $this->balances->sumCodes(self::ACCUM_DEP_CODES, $asOf, $branchId);
        $glNet = AccountingMoney::toFloat(AccountingMoney::sub($glGross, $glAccum));

        $registerCost = (float) FixedAsset::query()
            ->whereNotIn('status', [FixedAsset::STATUS_DRAFT, FixedAsset::STATUS_DISPOSED, FixedAsset::STATUS_RETIRED])
            ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
            ->sum('acquisition_cost');
        $registerAccum = (float) FixedAssetDepreciationEntry::query()
            ->where('status', FixedAssetDepreciationEntry::STATUS_POSTED)
            ->whereHas('asset', function ($query) use ($branchId): void {
                $query->whereNotIn('status', [FixedAsset::STATUS_DRAFT, FixedAsset::STATUS_DISPOSED, FixedAsset::STATUS_RETIRED]);
                if ($branchId) {
                    $query->where('branch_id', $branchId);
                }
            })
            ->sum('amount');
        $registerNet = AccountingMoney::toFloat(AccountingMoney::sub($registerCost, $registerAccum));
        $difference = AccountingMoney::toFloat(AccountingMoney::sub($glNet, $registerNet));

        return [
            'gl_gross' => $glGross,
            'gl_accumulated_depreciation' => $glAccum,
            'gl_net' => $glNet,
            'register_cost' => AccountingMoney::toFloat($registerCost),
            'register_accumulated_depreciation' => AccountingMoney::toFloat($registerAccum),
            'register_net' => $registerNet,
            'difference' => $difference,
            'ok' => AccountingMoney::isZero($difference),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildIncomeStatement(string $from, string $to, ?int $branchId): array
    {
        $accounts = $this->balances->balancesByAccount($from, $to, $branchId);
        $revenueItems = $this->itemsByType($accounts, Account::TYPE_REVENUE, self::RETURN_CODES, $from, $to, true);
        $returnItems = $this->itemsByCodes($accounts, self::RETURN_CODES, $from, $to);
        $grossRevenue = $this->sumItems($revenueItems);
        $returns = $this->sumItems($returnItems);
        $netRevenue = AccountingMoney::toFloat(AccountingMoney::sub($grossRevenue, $returns));

        $groups = [
            'cogs' => ['label' => 'تكلفة المبيعات', 'codes' => self::COGS_CODES],
            'operating' => ['label' => 'مصروفات تشغيلية', 'codes' => self::OPERATING_CODES],
            'salaries' => ['label' => 'رواتب', 'codes' => self::SALARY_CODES],
            'rent' => ['label' => 'إيجار', 'codes' => self::RENT_CODES],
            'utilities' => ['label' => 'خدمات', 'codes' => self::UTILITY_CODES],
            'marketing' => ['label' => 'تسويق', 'codes' => self::MARKETING_CODES],
            'depreciation' => ['label' => 'إهلاك', 'codes' => self::DEPRECIATION_CODES],
        ];

        $used = array_merge(self::COGS_CODES, self::OPERATING_CODES, self::SALARY_CODES, self::RENT_CODES, self::UTILITY_CODES, self::MARKETING_CODES, self::DEPRECIATION_CODES);
        $expenseGroups = [];
        foreach ($groups as $key => $meta) {
            $items = $this->itemsByCodes($accounts, $meta['codes'], $from, $to);
            $expenseGroups[$key] = [
                'key' => $key,
                'label' => $meta['label'],
                'items' => $items,
                'total' => $this->sumItems($items),
            ];
        }
        $otherItems = $this->itemsByType($accounts, Account::TYPE_EXPENSE, $used, $from, $to, true);
        $expenseGroups['other'] = [
            'key' => 'other',
            'label' => 'مصروفات أخرى',
            'items' => $otherItems,
            'total' => $this->sumItems($otherItems),
        ];

        $cogs = $expenseGroups['cogs']['total'];
        $operatingExpenses = AccountingMoney::toFloat(AccountingMoney::add(
            AccountingMoney::add($expenseGroups['operating']['total'], $expenseGroups['salaries']['total']),
            AccountingMoney::add(
                AccountingMoney::add($expenseGroups['rent']['total'], $expenseGroups['utilities']['total']),
                AccountingMoney::add($expenseGroups['marketing']['total'], $expenseGroups['depreciation']['total'])
            )
        ));
        $totalExpenses = AccountingMoney::toFloat(AccountingMoney::add($operatingExpenses, $expenseGroups['other']['total']));
        $grossProfit = AccountingMoney::toFloat(AccountingMoney::sub($netRevenue, $cogs));
        $operatingProfit = AccountingMoney::toFloat(AccountingMoney::sub($grossProfit, $operatingExpenses));
        $netProfit = AccountingMoney::toFloat(AccountingMoney::sub($operatingProfit, $expenseGroups['other']['total']));

        $allExpenseItems = [];
        foreach ($expenseGroups as $group) {
            foreach ($group['items'] as $item) {
                $allExpenseItems[] = $item;
            }
        }

        return [
            'period' => ['from' => $from, 'to' => $to],
            'revenues' => ['items' => $revenueItems, 'total' => $grossRevenue],
            'returns' => ['items' => $returnItems, 'total' => $returns],
            'gross_revenue' => $grossRevenue,
            'net_revenue' => $netRevenue,
            'expense_groups' => array_values($expenseGroups),
            'expenses' => ['items' => $allExpenseItems, 'total' => $totalExpenses],
            'cogs' => $cogs,
            'gross_profit' => $grossProfit,
            'operating_profit' => $operatingProfit,
            'net_profit' => $netProfit,
            'net_income' => $netProfit,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildBalanceSheet(string $asOf, ?int $branchId): array
    {
        $accounts = $this->balances->balancesByAccount(null, $asOf, $branchId);
        $cash = $this->group($accounts, 'النقدية', self::CASH_CODES, $asOf);
        $receivables = $this->group($accounts, 'العملاء', self::RECEIVABLE_CODES, $asOf);
        $inventory = $this->group($accounts, 'المخزون', self::INVENTORY_CODES, $asOf);
        $currentTotal = AccountingMoney::toFloat(AccountingMoney::add(
            AccountingMoney::add($cash['total'], $receivables['total']),
            $inventory['total']
        ));

        $fixed = $this->group($accounts, 'الأصول الثابتة', self::FIXED_ASSET_CODES, $asOf);
        $accum = $this->group($accounts, 'مجمع الإهلاك', self::ACCUM_DEP_CODES, $asOf);
        $netFixed = AccountingMoney::toFloat(AccountingMoney::sub($fixed['total'], $accum['total']));
        $nonCurrentTotal = $netFixed;
        $totalAssets = AccountingMoney::toFloat(AccountingMoney::add($currentTotal, $nonCurrentTotal));

        $payables = $this->group($accounts, 'الموردون', self::PAYABLE_CODES, $asOf);
        $loans = $this->group($accounts, 'القروض', self::LOAN_CODES, $asOf);
        $otherLiab = $this->group($accounts, 'التزامات أخرى', self::OTHER_LIABILITY_CODES, $asOf);
        $totalLiabilities = AccountingMoney::toFloat(AccountingMoney::add(
            AccountingMoney::add($payables['total'], $loans['total']),
            $otherLiab['total']
        ));

        $capital = $this->group($accounts, 'رأس المال', self::CAPITAL_CODES, $asOf);
        $drawings = $this->group($accounts, 'المسحوبات', self::DRAWINGS_CODES, $asOf);
        $retained = $this->balances->retainedEarnings($accounts);
        $retainedGroup = [
            'key' => 'retained_earnings',
            'label' => 'الأرباح المتراكمة',
            'items' => [[
                'id' => 0,
                'account_id' => null,
                'code' => '3999',
                'name' => 'الأرباح المتراكمة / صافي الدخل غير الموزع',
                'current_balance' => $retained,
                'drill' => null,
            ]],
            'total' => $retained,
        ];
        $totalEquity = AccountingMoney::toFloat(AccountingMoney::sub(
            AccountingMoney::add($capital['total'], $retained),
            $drawings['total']
        ));
        $liabilitiesAndEquity = AccountingMoney::toFloat(AccountingMoney::add($totalLiabilities, $totalEquity));
        $difference = AccountingMoney::toFloat(AccountingMoney::sub($totalAssets, $liabilitiesAndEquity));
        $balanced = AccountingMoney::isZero($difference);

        $flatAssets = $this->balances->reportSection($accounts, 'asset');
        $flatLiabilities = $this->balances->reportSection($accounts, 'liability');
        $flatEquity = $this->balances->reportSection($accounts, 'equity');
        if (abs($retained) >= 0.005) {
            $flatEquity['items'][] = [
                'id' => 0,
                'code' => '3999',
                'name' => 'الأرباح المتراكمة / صافي الدخل غير الموزع',
                'current_balance' => $retained,
            ];
            $flatEquity['total'] = AccountingMoney::toFloat(AccountingMoney::add($flatEquity['total'], $retained));
        }

        return [
            'as_of' => $asOf,
            'structure' => [
                'assets' => [
                    'current' => [
                        'label' => 'أصول متداولة',
                        'groups' => [$cash, $receivables, $inventory],
                        'total' => $currentTotal,
                    ],
                    'non_current' => [
                        'label' => 'أصول غير متداولة',
                        'groups' => [$fixed, $accum],
                        'net_fixed_assets' => $netFixed,
                        'total' => $nonCurrentTotal,
                    ],
                    'total' => $totalAssets,
                ],
                'liabilities' => [
                    'groups' => [$payables, $loans, $otherLiab],
                    'total' => $totalLiabilities,
                ],
                'equity' => [
                    'groups' => [$capital, $drawings, $retainedGroup],
                    'total' => $totalEquity,
                ],
            ],
            'assets' => $flatAssets,
            'liabilities' => $flatLiabilities,
            'equity' => $flatEquity,
            'total_assets' => $totalAssets,
            'total_liabilities' => $totalLiabilities,
            'total_equity' => $totalEquity,
            'liabilities_and_equity' => $liabilitiesAndEquity,
            'difference' => $difference,
            'balanced' => $balanced,
            'equation' => [
                'assets' => $totalAssets,
                'liabilities_plus_equity' => $liabilitiesAndEquity,
                'difference' => $difference,
                'balanced' => $balanced,
                'message' => $balanced ? 'الميزانية متزنة' : '🔴 يوجد عدم اتزان محاسبي',
            ],
        ];
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $accounts
     * @param  list<string>  $exclude
     * @return list<array<string, mixed>>
     */
    private function itemsByType(Collection $accounts, string $type, array $exclude, string $from, string $to, bool $postingOnly = true): array
    {
        return $accounts
            ->where('type', $type)
            ->filter(fn (array $account): bool => ! in_array((string) $account['code'], $exclude, true))
            ->filter(fn (array $account): bool => ! $postingOnly || ($account['allow_posting'] ?? true) === true)
            ->filter(fn (array $account): bool => abs((float) $account['current_balance']) >= 0.005)
            ->values()
            ->map(fn (array $account): array => $this->line($account, $from, $to))
            ->all();
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $accounts
     * @param  list<string>  $codes
     * @return list<array<string, mixed>>
     */
    private function itemsByCodes(Collection $accounts, array $codes, string $from, string $to): array
    {
        return $accounts
            ->filter(fn (array $account): bool => in_array((string) $account['code'], $codes, true))
            ->filter(fn (array $account): bool => abs((float) $account['current_balance']) >= 0.005)
            ->values()
            ->map(fn (array $account): array => $this->line($account, $from, $to))
            ->all();
    }

    /**
     * @param  array<string, mixed>  $account
     * @return array<string, mixed>
     */
    private function line(array $account, string $from, string $to): array
    {
        return [
            'id' => $account['id'],
            'account_id' => $account['id'],
            'code' => $account['code'],
            'name' => $account['name'],
            'current_balance' => $account['current_balance'],
            'drill' => $this->accountDrill((int) $account['id'], $from, $to),
        ];
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $accounts
     * @param  list<string>  $codes
     * @return array<string, mixed>
     */
    private function group(Collection $accounts, string $label, array $codes, string $asOf): array
    {
        $items = $this->itemsByCodes($accounts, $codes, '1970-01-01', $asOf);

        return [
            'key' => $codes[0] ?? $label,
            'label' => $label,
            'codes' => $codes,
            'items' => $items,
            'total' => $this->sumItems($items),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $items
     */
    private function sumItems(array $items): float
    {
        $total = AccountingMoney::zero();
        foreach ($items as $item) {
            $total = AccountingMoney::add($total, $item['current_balance'] ?? 0);
        }

        return AccountingMoney::toFloat($total);
    }

    /**
     * @param  list<array<string, mixed>>  $defs
     * @return array{items: list<array<string, mixed>>, total: float}
     */
    private function cashFlowSection(array $defs, string $from, string $to, ?int $branchId): array
    {
        $items = [];
        $total = AccountingMoney::zero();
        foreach ($defs as $def) {
            $movement = $this->balances->periodMovementByCodes($def['codes'], $from, $to, $branchId);
            $net = AccountingMoney::sub($movement['debit'], $movement['credit']);
            if (! empty($def['liability'])) {
                $net = AccountingMoney::sub($movement['credit'], $movement['debit']);
            }
            if (! empty($def['working_capital']) && empty($def['liability'])) {
                $net = AccountingMoney::sub($movement['credit'], $movement['debit']);
            }
            if (! empty($def['invert'])) {
                $net = AccountingMoney::sub('0', $net);
            }
            $amount = AccountingMoney::toFloat($net);
            $items[] = [
                'key' => $def['key'],
                'label' => $def['label'],
                'amount' => $amount,
                'codes' => $def['codes'],
            ];
            $total = AccountingMoney::add($total, $net);
        }

        return [
            'items' => $items,
            'total' => AccountingMoney::toFloat($total),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function treasuryReconciliation(float $glCash, ?int $branchId): array
    {
        $treasury = (float) Cashbox::query()
            ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
            ->sum('current_balance');
        $difference = AccountingMoney::toFloat(AccountingMoney::sub($glCash, $treasury));

        return [
            'gl_cash' => $glCash,
            'treasury_balance' => AccountingMoney::toFloat($treasury),
            'difference' => $difference,
            'ok' => AccountingMoney::isZero($difference),
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function tbDebit(array $row): string
    {
        $closing = AccountingMoney::of($row['closing_balance'] ?? 0);
        $type = (string) ($row['type'] ?? '');
        if (in_array($type, [Account::TYPE_ASSET, Account::TYPE_EXPENSE], true)) {
            return AccountingMoney::isPositive($closing) ? $closing : AccountingMoney::zero();
        }

        return AccountingMoney::isPositive($closing) ? AccountingMoney::zero() : AccountingMoney::sub('0', $closing);
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function tbCredit(array $row): string
    {
        $closing = AccountingMoney::of($row['closing_balance'] ?? 0);
        $type = (string) ($row['type'] ?? '');
        if (in_array($type, [Account::TYPE_ASSET, Account::TYPE_EXPENSE], true)) {
            return AccountingMoney::isPositive($closing) ? AccountingMoney::zero() : AccountingMoney::sub('0', $closing);
        }

        return AccountingMoney::isPositive($closing) ? $closing : AccountingMoney::zero();
    }

    /**
     * @return array{type: string, account_id: int, date_from: string, date_to: string, path: string}
     */
    private function accountDrill(int $accountId, string $from, string $to): array
    {
        return [
            'type' => 'account',
            'account_id' => $accountId,
            'date_from' => $from,
            'date_to' => $to,
            'path' => '/accounting/ledger?account_id='.$accountId.'&date_from='.$from.'&date_to='.$to,
        ];
    }

    /**
     * @return list<string>
     */
    private function revenueCodes(): array
    {
        return Account::query()->where('type', Account::TYPE_REVENUE)->where('allow_posting', true)->pluck('code')->all();
    }

    /**
     * @return list<string>
     */
    private function cashExpenseCodes(): array
    {
        return Account::query()
            ->where('type', Account::TYPE_EXPENSE)
            ->where('allow_posting', true)
            ->whereNotIn('code', self::DEPRECIATION_CODES)
            ->pluck('code')
            ->all();
    }

    /**
     * @param  array<string, mixed>  $current
     * @param  array<string, mixed>  $compare
     * @return array<string, mixed>
     */
    private function incomeVariance(array $current, array $compare): array
    {
        $keys = ['gross_revenue', 'net_revenue', 'cogs', 'gross_profit', 'operating_profit', 'net_profit'];
        $out = [];
        foreach ($keys as $key) {
            $out[$key] = $this->delta((float) ($current[$key] ?? 0), (float) ($compare[$key] ?? 0));
        }

        return $out;
    }

    /**
     * @param  array<string, mixed>  $current
     * @param  array<string, mixed>  $compare
     * @return array<string, mixed>
     */
    private function balanceVariance(array $current, array $compare): array
    {
        $keys = ['total_assets', 'total_liabilities', 'total_equity'];
        $out = [];
        foreach ($keys as $key) {
            $out[$key] = $this->delta((float) ($current[$key] ?? 0), (float) ($compare[$key] ?? 0));
        }

        return $out;
    }

    /**
     * @return array{current: float, previous: float, difference: float, percent: float|null}
     */
    private function delta(float $current, float $previous): array
    {
        $difference = AccountingMoney::toFloat(AccountingMoney::sub($current, $previous));
        $percent = abs($previous) < 0.005 ? null : round(($difference / $previous) * 100, 2);

        return [
            'current' => $current,
            'previous' => $previous,
            'difference' => $difference,
            'percent' => $percent,
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function wantsCompare(array $filters): bool
    {
        $value = $filters['compare'] ?? $filters['comparative'] ?? false;

        return filter_var($value, FILTER_VALIDATE_BOOLEAN) || $value === '1' || $value === 1;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @param  array{from: string, to: string}  $period
     * @return array{from: string, to: string}
     */
    private function compareRange(array $filters, array $period): array
    {
        $from = trim((string) ($filters['compare_from'] ?? ''));
        $to = trim((string) ($filters['compare_to'] ?? ''));
        if ($from !== '' && $to !== '') {
            return ['from' => $from, 'to' => $to];
        }

        return ReportDateRange::previous($period);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function asOf(array $filters): string
    {
        $asOf = trim((string) ($filters['date'] ?? $filters['date_to'] ?? now()->toDateString()));

        return $asOf !== '' ? $asOf : now()->toDateString();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function branchId(array $filters): ?int
    {
        $branchId = $filters['branch_id'] ?? null;
        if ($branchId === null || $branchId === '') {
            return null;
        }

        return (int) $branchId;
    }
}
