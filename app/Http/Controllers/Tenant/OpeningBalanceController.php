<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\Accounting\StoreOpeningBalanceRequest;
use App\Http\Resources\Tenant\JournalEntryResource;
use App\Services\Tenant\OpeningBalanceService;
use App\Support\ApiResponse;
use App\Support\Reports\TabularExport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OpeningBalanceController extends Controller
{
    public function __construct(private readonly OpeningBalanceService $openingBalances) {}

    public function show(): JsonResponse
    {
        return ApiResponse::success([
            'worksheet' => $this->openingBalances->worksheet(),
            'batch' => $this->serializeBatch($this->openingBalances->latest()),
        ]);
    }

    public function store(StoreOpeningBalanceRequest $request): JsonResponse
    {
        $batch = $this->openingBalances->saveDraft($request->validated(), $request->user()?->id);

        return ApiResponse::success($this->serializeBatch($batch), 'Opening balances saved');
    }

    public function post(Request $request, int $batch): JsonResponse
    {
        $model = $this->openingBalances->findOrFail($batch);
        $model = $this->openingBalances->post($model, $request->user()?->id);

        return ApiResponse::success($this->serializeBatch($model), 'Opening journal posted');
    }

    public function export(Request $request): StreamedResponse|Response
    {
        return TabularExport::download(
            $request->query('format'),
            'opening-balances',
            'الأرصدة الافتتاحية',
            ['رمز الحساب', 'اسم الحساب', 'مدين', 'دائن', 'البيان'],
            $this->openingBalances->exportRows(),
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    private function serializeBatch($batch): ?array
    {
        if ($batch === null) {
            return null;
        }

        return [
            'id' => $batch->id,
            'entry_date' => $batch->entry_date?->toDateString(),
            'branch_id' => $batch->branch_id,
            'status' => $batch->status,
            'description' => $batch->description,
            'total_debit' => (float) $batch->total_debit,
            'total_credit' => (float) $batch->total_credit,
            'is_balanced' => (bool) $batch->is_balanced,
            'journal_entry_id' => $batch->journal_entry_id,
            'journal' => $batch->journalEntry
                ? (new JournalEntryResource($batch->journalEntry))->resolve()
                : null,
            'created_by' => $batch->creator?->name,
            'posted_by' => $batch->poster?->name,
            'posted_at' => $batch->posted_at?->toIso8601String(),
            'lines' => $batch->lines->map(fn ($line) => [
                'account_id' => $line->account_id,
                'account_code' => $line->account?->code,
                'account_name' => $line->account?->name,
                'debit' => (float) $line->debit,
                'credit' => (float) $line->credit,
                'description' => $line->description,
            ])->all(),
        ];
    }
}
