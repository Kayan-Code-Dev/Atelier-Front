<?php

namespace App\Http\Controllers\Tenant;

use App\Accounting\AccountingAuditService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\JournalEntry\CancelJournalEntryRequest;
use App\Http\Requests\Tenant\JournalEntry\ReverseJournalEntryRequest;
use App\Http\Requests\Tenant\JournalEntry\StoreJournalEntryRequest;
use App\Http\Requests\Tenant\JournalEntry\UpdateJournalEntryRequest;
use App\Http\Resources\Tenant\JournalEntryResource;
use App\Models\Tenant\Account;
use App\Services\Tenant\JournalEntryService;
use App\Support\ApiResponse;
use App\Support\Reports\TabularExport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class JournalEntryController extends Controller
{
    public function __construct(
        private readonly JournalEntryService $journalEntryService,
        private readonly AccountingAuditService $audit,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = max(1, min(100, $request->integer('per_page', 15)));
        $paginator = $this->journalEntryService->paginate($this->filters($request), $perPage);

        return ApiResponse::paginated(
            $paginator,
            JournalEntryResource::collection($paginator->items())->resolve(),
        );
    }

    public function summary(Request $request): JsonResponse
    {
        return ApiResponse::success($this->journalEntryService->summary($this->filters($request)));
    }

    public function store(StoreJournalEntryRequest $request): JsonResponse
    {
        $entry = $this->journalEntryService->create($request->validated(), $request->user()?->id);

        return ApiResponse::success(new JournalEntryResource($entry), 'Journal entry created', 201);
    }

    public function show(int $journalEntry): JsonResponse
    {
        $entry = $this->journalEntryService->findOrFail($journalEntry);

        $payload = (new JournalEntryResource($entry))->resolve();
        $payload['audit_timeline'] = $this->audit->timeline('journal_entry', $entry->id);

        return ApiResponse::success($payload);
    }

    public function update(UpdateJournalEntryRequest $request, int $journalEntry): JsonResponse
    {
        $entry = $this->journalEntryService->findOrFail($journalEntry);
        $entry = $this->journalEntryService->update($entry, $request->validated(), $request->user()?->id);

        return ApiResponse::success(new JournalEntryResource($entry), 'Journal entry updated');
    }

    public function destroy(Request $request, int $journalEntry): JsonResponse
    {
        $entry = $this->journalEntryService->findOrFail($journalEntry);
        $this->journalEntryService->deleteDraft($entry, $request->user()?->id);

        return ApiResponse::success(null, 'Journal entry deleted');
    }

    public function approve(Request $request, int $journalEntry): JsonResponse
    {
        $entry = $this->journalEntryService->findOrFail($journalEntry);
        $entry = $this->journalEntryService->approve($entry, $request->user()?->id);

        return ApiResponse::success(new JournalEntryResource($entry), 'Journal entry posted');
    }

    public function submit(Request $request, int $journalEntry): JsonResponse
    {
        $entry = $this->journalEntryService->findOrFail($journalEntry);
        $entry = $this->journalEntryService->submit($entry, $request->user()?->id);

        return ApiResponse::success(new JournalEntryResource($entry), 'Journal entry submitted');
    }

    public function accept(Request $request, int $journalEntry): JsonResponse
    {
        $entry = $this->journalEntryService->findOrFail($journalEntry);
        $entry = $this->journalEntryService->accept($entry, $request->user()?->id);

        return ApiResponse::success(new JournalEntryResource($entry), 'Journal entry approved');
    }

    public function post(Request $request, int $journalEntry): JsonResponse
    {
        $entry = $this->journalEntryService->findOrFail($journalEntry);
        $entry = $this->journalEntryService->post($entry, $request->user()?->id);

        return ApiResponse::success(new JournalEntryResource($entry), 'Journal entry posted');
    }

    public function cancel(CancelJournalEntryRequest $request, int $journalEntry): JsonResponse
    {
        $entry = $this->journalEntryService->findOrFail($journalEntry);
        $entry = $this->journalEntryService->cancel(
            $entry,
            $request->validated('cancellation_reason'),
            $request->user()?->id,
        );

        return ApiResponse::success(new JournalEntryResource($entry), 'Journal entry cancelled');
    }

    public function reverse(ReverseJournalEntryRequest $request, int $journalEntry): JsonResponse
    {
        $entry = $this->journalEntryService->findOrFail($journalEntry);
        $reversal = $this->journalEntryService->reverse(
            $entry,
            $request->user()?->id,
            $request->validated('reversal_reason'),
        );

        return ApiResponse::success(new JournalEntryResource($reversal), 'Reversal journal entry created', 201);
    }

    public function export(Request $request): StreamedResponse|Response
    {
        $rows = $this->journalEntryService->exportRows($this->filters($request));
        $headers = [
            'رقم القيد',
            'تاريخ القيد',
            'النوع',
            'نوع المصدر',
            'المرجع',
            'البيان',
            'إجمالي المدين',
            'إجمالي الدائن',
            'الفرق',
            'الحالة',
            'الفرع',
            'أنشئ بواسطة',
        ];

        $orderedRows = array_map(static function (array $row) use ($headers): array {
            $map = [
                'رقم القيد' => $row['entry_number'] ?? '',
                'تاريخ القيد' => $row['entry_date'] ?? '',
                'النوع' => $row['type'] ?? '',
                'نوع المصدر' => $row['source_type'] ?? '',
                'المرجع' => $row['reference_number'] ?? '',
                'البيان' => $row['description'] ?? '',
                'إجمالي المدين' => $row['total_debit'] ?? 0,
                'إجمالي الدائن' => $row['total_credit'] ?? 0,
                'الفرق' => $row['difference'] ?? 0,
                'الحالة' => $row['status'] ?? '',
                'الفرع' => $row['branch'] ?? '',
                'أنشئ بواسطة' => $row['created_by'] ?? '',
            ];

            return array_map(static fn (string $header) => $map[$header] ?? '', $headers);
        }, $rows);

        return TabularExport::download(
            $request->query('format'),
            'journal-entries',
            'القيود المحاسبية',
            $headers,
            $orderedRows,
        );
    }

    public function accounts(): JsonResponse
    {
        $accounts = Account::query()
            ->where('is_active', true)
            ->where('allow_posting', true)
            ->orderBy('code')
            ->get(['id', 'code', 'name', 'type']);

        return ApiResponse::success($accounts);
    }

    /**
     * @return array<string, mixed>
     */
    private function filters(Request $request): array
    {
        return [
            'search' => $request->query('search'),
            'date_from' => $request->query('date_from'),
            'date_to' => $request->query('date_to'),
            'status' => $request->query('status'),
            'type' => $request->query('type'),
            'source_type' => $request->query('source_type'),
            'branch_id' => $request->query('branch_id'),
            'account_id' => $request->query('account_id'),
            'created_by' => $request->query('created_by'),
            'is_balanced' => $request->query('is_balanced'),
        ];
    }
}
