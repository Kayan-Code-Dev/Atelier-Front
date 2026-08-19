<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Services\Tenant\BankReconciliationService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BankReconciliationController extends Controller
{
    public function __construct(private readonly BankReconciliationService $reconciliations) {}

    public function index(Request $request): JsonResponse
    {
        return ApiResponse::success($this->reconciliations->list($this->filters($request)));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'bank_account_id' => ['required', 'integer'],
            'date_from' => ['required', 'date'],
            'date_to' => ['required', 'date'],
            'statement_balance' => ['required', 'numeric'],
            'notes' => ['nullable', 'string'],
        ]);

        $recon = $this->reconciliations->start($data, $request->user()?->id, $this->filters($request));

        return ApiResponse::success($this->reconciliations->detail($recon), 'تم بدء التسوية', 201);
    }

    public function show(Request $request, int $reconciliation): JsonResponse
    {
        $recon = $this->reconciliations->findOrFail($reconciliation, $this->filters($request));

        return ApiResponse::success($this->reconciliations->detail($recon));
    }

    public function previewImport(Request $request, int $reconciliation): JsonResponse
    {
        $request->validate(['file' => ['required', 'file', 'max:5120']]);
        $recon = $this->reconciliations->findOrFail($reconciliation, $this->filters($request));

        return ApiResponse::success($this->reconciliations->previewImport($recon, $request->file('file')));
    }

    public function import(Request $request, int $reconciliation): JsonResponse
    {
        $request->validate([
            'file' => ['nullable', 'file', 'max:5120'],
            'rows' => ['nullable', 'array'],
            'rows.*.date' => ['required_with:rows', 'date'],
            'rows.*.description' => ['nullable', 'string', 'max:255'],
            'rows.*.reference' => ['nullable', 'string', 'max:120'],
            'rows.*.debit' => ['nullable', 'numeric'],
            'rows.*.credit' => ['nullable', 'numeric'],
            'rows.*.amount' => ['nullable', 'numeric'],
        ]);
        $recon = $this->reconciliations->findOrFail($reconciliation, $this->filters($request));
        $import = $this->reconciliations->import(
            $recon,
            $request->file('file'),
            $request->input('rows'),
            $request->user()?->id
        );

        return ApiResponse::success([
            'import_id' => $import->id,
            'row_count' => $import->row_count,
            'filename' => $import->original_filename,
            'reconciliation' => $this->reconciliations->detail($recon->fresh() ?? $recon),
        ], 'تم استيراد كشف البنك');
    }

    public function autoMatch(Request $request, int $reconciliation): JsonResponse
    {
        $recon = $this->reconciliations->findOrFail($reconciliation, $this->filters($request));
        $matches = $this->reconciliations->autoMatch($recon, $request->user()?->id);

        return ApiResponse::success([
            'matches' => $matches,
            'reconciliation' => $this->reconciliations->detail($recon->fresh() ?? $recon),
        ], 'تمت المطابقة التلقائية للحركات ذات الثقة العالية فقط');
    }

    public function match(Request $request, int $reconciliation): JsonResponse
    {
        $data = $request->validate([
            'statement_line_id' => ['required', 'integer'],
            'journal_entry_line_id' => ['required', 'integer'],
        ]);
        $recon = $this->reconciliations->findOrFail($reconciliation, $this->filters($request));
        $match = $this->reconciliations->manualMatch(
            $recon,
            (int) $data['statement_line_id'],
            (int) $data['journal_entry_line_id'],
            $request->user()?->id
        );

        return ApiResponse::success([
            'match' => [
                'id' => $match->id,
                'grade' => $match->grade,
                'match_type' => $match->match_type,
                'journal_entry_id' => $match->journal_entry_id,
            ],
            'reconciliation' => $this->reconciliations->detail($recon->fresh() ?? $recon),
        ], 'تم ربط الحركة دون تعديل القيد الأصلي');
    }

    public function adjust(Request $request, int $reconciliation): JsonResponse
    {
        $data = $request->validate([
            'kind' => ['required', 'in:bank_fee,interest_income,interest_expense,other'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'description' => ['nullable', 'string', 'max:255'],
            'entry_date' => ['nullable', 'date'],
            'statement_line_id' => ['nullable', 'integer'],
            'expense_account_id' => ['nullable', 'integer'],
        ]);
        $recon = $this->reconciliations->findOrFail($reconciliation, $this->filters($request));
        $adjustment = $this->reconciliations->createAdjustment($recon, $data, $request->user()?->id);

        return ApiResponse::success([
            'adjustment_id' => $adjustment->id,
            'journal_entry_id' => $adjustment->journal_entry_id,
            'source_type' => 'bank_reconciliation',
            'source_id' => $adjustment->id,
            'reconciliation' => $this->reconciliations->detail($recon->fresh() ?? $recon),
        ], 'تم إنشاء قيد التسوية عبر محرك القيود');
    }

    public function submit(Request $request, int $reconciliation): JsonResponse
    {
        $recon = $this->reconciliations->findOrFail($reconciliation, $this->filters($request));

        return ApiResponse::success(
            $this->reconciliations->detail($this->reconciliations->submit($recon, $request->user()?->id)),
            'أُرسلت التسوية للمراجعة'
        );
    }

    public function reconcile(Request $request, int $reconciliation): JsonResponse
    {
        $recon = $this->reconciliations->findOrFail($reconciliation, $this->filters($request));

        return ApiResponse::success(
            $this->reconciliations->detail($this->reconciliations->markReconciled($recon, $request->user()?->id)),
            'Account Reconciled'
        );
    }

    public function lock(Request $request, int $reconciliation): JsonResponse
    {
        $recon = $this->reconciliations->findOrFail($reconciliation, $this->filters($request));

        return ApiResponse::success(
            $this->reconciliations->detail($this->reconciliations->lock($recon, $request->user()?->id)),
            'تم قفل التسوية'
        );
    }

    public function reopen(Request $request, int $reconciliation): JsonResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:500']]);
        $recon = $this->reconciliations->findOrFail($reconciliation, $this->filters($request));

        return ApiResponse::success(
            $this->reconciliations->detail($this->reconciliations->reopen($recon, $data['reason'], $request->user()?->id)),
            'أُعيد فتح التسوية مع سجل تدقيق'
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function filters(Request $request): array
    {
        return [
            'status' => $request->query('status'),
            'bank_account_id' => $request->query('bank_account_id'),
            'branch_id' => $request->query('branch_id'),
            'user' => $request->user(),
        ];
    }
}
