<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Services\Tenant\AccountingControlService;
use App\Services\Tenant\AccountingService;
use App\Services\Tenant\FinancialStatementService;
use App\Services\Tenant\TenantContext;
use App\Support\ApiResponse;
use App\Support\Reports\TabularExport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AccountingController extends Controller
{
    public function __construct(
        private readonly AccountingService $accountingService,
        private readonly FinancialStatementService $statements,
        private readonly AccountingControlService $controls,
        private readonly TenantContext $tenantContext,
    ) {}

    public function summary(Request $request): JsonResponse
    {
        return ApiResponse::success($this->accountingService->summary($this->filters($request, 'year')));
    }

    public function ledger(Request $request): JsonResponse
    {
        $filters = $this->filters($request, 'year');
        if ((int) ($filters['account_id'] ?? 0) > 0) {
            $statement = $this->accountingService->ledgerStatement($filters);

            return ApiResponse::success($statement['lines'], 'Success', 200, [
                'account' => $statement['account'],
                'opening_balance' => $statement['opening_balance'],
                'closing_balance' => $statement['closing_balance'],
                'debit_total' => $statement['debit_total'],
                'credit_total' => $statement['credit_total'],
            ]);
        }

        return ApiResponse::success($this->accountingService->ledger($filters));
    }

    public function ledgerExport(Request $request): StreamedResponse|Response
    {
        $filters = $this->filters($request, 'year');
        $accountId = (int) ($filters['account_id'] ?? 0);
        if ($accountId <= 0) {
            return ApiResponse::error('account_id is required', 422);
        }

        $statement = $this->accountingService->ledgerStatement($filters);
        $rows = array_map(static fn (array $line): array => [
            $line['date'] ?? '',
            $line['journal_number'] ?? $line['reference'] ?? '',
            $line['source'] ?? '',
            $line['description'] ?? '',
            $line['debit'] ?? 0,
            $line['credit'] ?? 0,
            $line['balance'] ?? 0,
        ], $statement['lines']);

        return $this->export($request, 'account-ledger', 'كشف الحساب', [
            'التاريخ', 'المرجع', 'المصدر', 'البيان', 'مدين', 'دائن', 'الرصيد',
        ], $rows, $filters);
    }

    public function generalLedger(Request $request): JsonResponse
    {
        return ApiResponse::success($this->accountingService->generalLedger($this->filters($request, 'year')));
    }

    public function generalLedgerExport(Request $request): StreamedResponse|Response
    {
        $filters = $this->filters($request, 'year');
        $rows = array_map(static fn (array $row): array => [
            $row['code'] ?? '',
            $row['name'] ?? '',
            $row['type'] ?? '',
            $row['opening_balance'] ?? 0,
            $row['debit'] ?? 0,
            $row['credit'] ?? 0,
            $row['closing_balance'] ?? 0,
        ], $this->accountingService->generalLedger($filters));

        return $this->export($request, 'general-ledger', 'دفتر الأستاذ العام', [
            'رمز الحساب', 'اسم الحساب', 'النوع', 'رصيد افتتاحي', 'مدين', 'دائن', 'رصيد ختامي',
        ], $rows, $filters);
    }

    public function accountsTree(Request $request): JsonResponse
    {
        return ApiResponse::success($this->accountingService->accountsTree($this->filters($request, 'year')));
    }

    public function balanceSheet(Request $request): JsonResponse
    {
        return ApiResponse::success($this->statements->balanceSheet($this->filters($request, 'year')));
    }

    public function incomeStatement(Request $request): JsonResponse
    {
        return ApiResponse::success($this->statements->incomeStatement($this->filters($request, 'month')));
    }

    public function trialBalance(Request $request): JsonResponse
    {
        return ApiResponse::success($this->statements->trialBalance($this->filters($request, 'month')));
    }

    public function cashFlow(Request $request): JsonResponse
    {
        return ApiResponse::success($this->statements->cashFlow($this->filters($request, 'month')));
    }

    public function incomeStatementExport(Request $request): StreamedResponse|Response
    {
        $filters = $this->filters($request, 'month');
        $data = $this->statements->incomeStatement($filters);
        $rows = [
            ['إجمالي الإيرادات', $data['gross_revenue'] ?? 0, $data['compare']['gross_revenue'] ?? ''],
            ['مردودات/خصومات', $data['returns']['total'] ?? 0, $data['compare']['returns']['total'] ?? ''],
            ['صافي الإيرادات', $data['net_revenue'] ?? 0, $data['compare']['net_revenue'] ?? ''],
        ];
        foreach ($data['expense_groups'] ?? [] as $group) {
            $rows[] = [$group['label'] ?? '', $group['total'] ?? 0, ''];
        }
        $rows[] = ['مجمل الربح', $data['gross_profit'] ?? 0, $data['compare']['gross_profit'] ?? ''];
        $rows[] = ['الربح التشغيلي', $data['operating_profit'] ?? 0, $data['compare']['operating_profit'] ?? ''];
        $rows[] = ['صافي الربح', $data['net_profit'] ?? 0, $data['compare']['net_profit'] ?? ''];

        return $this->export($request, 'income-statement', 'قائمة الدخل', ['البند', 'الفترة', 'المقارنة'], $rows, $filters);
    }

    public function balanceSheetExport(Request $request): StreamedResponse|Response
    {
        $filters = $this->filters($request, 'year');
        $data = $this->statements->balanceSheet($filters);
        $rows = [];
        foreach (['assets' => 'الأصول', 'liabilities' => 'الالتزامات', 'equity' => 'حقوق الملكية'] as $key => $label) {
            $rows[] = [$label, $data[$key]['total'] ?? 0, ''];
            foreach ($data[$key]['items'] ?? [] as $item) {
                $rows[] = [($item['code'] ?? '').' '.($item['name'] ?? ''), $item['current_balance'] ?? 0, ''];
            }
        }
        $rows[] = ['إجمالي الأصول', $data['total_assets'] ?? 0, ''];
        $rows[] = ['الالتزامات + حقوق الملكية', $data['liabilities_and_equity'] ?? 0, ''];
        $rows[] = [$data['equation']['message'] ?? '', $data['equation']['difference'] ?? 0, ''];

        return $this->export($request, 'balance-sheet', 'الميزانية العمومية', ['البند', 'المبلغ', 'مقارنة'], $rows, $filters);
    }

    public function trialBalanceExport(Request $request): StreamedResponse|Response
    {
        $filters = $this->filters($request, 'month');
        $data = $this->statements->trialBalance($filters);
        $rows = array_map(static fn (array $line): array => [
            $line['code'] ?? '',
            $line['name'] ?? '',
            $line['debit'] ?? 0,
            $line['credit'] ?? 0,
        ], $data['lines'] ?? []);
        $rows[] = ['', 'الإجمالي', $data['total_debit'] ?? 0, $data['total_credit'] ?? 0];

        return $this->export($request, 'trial-balance', 'ميزان المراجعة', ['الحساب', 'الاسم', 'مدين', 'دائن'], $rows, $filters);
    }

    public function cashFlowExport(Request $request): StreamedResponse|Response
    {
        $filters = $this->filters($request, 'month');
        $data = $this->statements->cashFlow($filters);
        $rows = [
            ['النقدية الافتتاحية', $data['opening_cash'] ?? 0],
            ['التشغيل', $data['operating']['total'] ?? 0],
            ['الاستثمار', $data['investing']['total'] ?? 0],
            ['التمويل', $data['financing']['total'] ?? 0],
            ['صافي الحركة', $data['net_cash_movement'] ?? 0],
            ['النقدية الختامية', $data['closing_cash'] ?? 0],
        ];

        return $this->export($request, 'cash-flow', 'قائمة التدفقات النقدية', ['البند', 'المبلغ'], $rows, $filters);
    }

    public function controls(Request $request): JsonResponse
    {
        return ApiResponse::success($this->controls->dashboard($this->filters($request, 'month')));
    }

    public function unposted(Request $request): JsonResponse
    {
        return ApiResponse::success($this->controls->unposted($this->filters($request, 'month')));
    }

    public function exceptions(Request $request): JsonResponse
    {
        return ApiResponse::success($this->controls->exceptions($this->filters($request, 'month')));
    }

    public function treasuryAccounts(Request $request): JsonResponse
    {
        return ApiResponse::success($this->accountingService->treasuryAccounts($this->filters($request, 'year')));
    }

    /**
     * @return array<string, mixed>
     */
    private function filters(Request $request, string $defaultPeriod): array
    {
        $period = $request->query('period');
        $dateFrom = $request->query('date_from') ?? $request->query('from_date');
        $dateTo = $request->query('date_to') ?? $request->query('to_date');

        return [
            'period' => $period ?: $defaultPeriod,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'date' => $request->query('date'),
            'branch_id' => $request->query('branch_id'),
            'search' => $request->query('search'),
            'account_id' => $request->query('account_id'),
            'account_type' => $request->query('account_type'),
            'source_type' => $request->query('source_type'),
            'reference' => $request->query('reference'),
            'status' => $request->query('status'),
            'compare' => $request->query('compare'),
            'compare_from' => $request->query('compare_from'),
            'compare_to' => $request->query('compare_to'),
            'compare_date' => $request->query('compare_date'),
            'include_zeros' => $request->query('include_zeros'),
        ];
    }

    /**
     * @param  list<string>  $headers
     * @param  list<list<int|string|float|null>>  $rows
     * @param  array<string, mixed>  $filters
     */
    private function export(
        Request $request,
        string $basename,
        string $title,
        array $headers,
        array $rows,
        array $filters = [],
    ): StreamedResponse|Response {
        return TabularExport::download(
            $request->query('format'),
            $basename,
            $title,
            $headers,
            $rows,
            [
                'المنشأة' => $this->tenantContext->tenant()?->name ?? '',
                'الفرع' => $filters['branch_id'] ?? 'كل الفروع',
                'الفترة' => trim(($filters['date_from'] ?? '').' — '.($filters['date_to'] ?? '')),
                'تاريخ التصدير' => now()->toDateTimeString(),
                'المستخدم' => $request->user()?->name ?? '',
            ]
        );
    }
}
