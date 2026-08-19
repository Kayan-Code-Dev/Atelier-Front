<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\Accounting\TreasuryOperationRequest;
use App\Http\Requests\Tenant\Accounting\TreasuryTransferRequest;
use App\Http\Requests\Tenant\Cashbox\StoreCashboxRequest;
use App\Http\Requests\Tenant\Cashbox\UpdateCashboxRequest;
use App\Http\Resources\Tenant\CashboxResource;
use App\Http\Resources\Tenant\CashMovementResource;
use App\Http\Resources\Tenant\JournalEntryResource;
use App\Services\Tenant\CashboxService;
use App\Services\Tenant\TreasuryOperationService;
use App\Support\ApiResponse;
use App\Support\Reports\TabularExport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CashboxController extends Controller
{
    public function __construct(
        private readonly CashboxService $cashboxService,
        private readonly TreasuryOperationService $treasuryOperations,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = max(1, min(100, $request->integer('per_page', 15)));
        $cashboxes = $this->cashboxService->paginate([
            'search' => $request->query('search'),
            'branch_id' => $request->query('branch_id'),
            'is_active' => $request->query('is_active'),
        ], $perPage);

        return ApiResponse::paginated($cashboxes, CashboxResource::collection($cashboxes->items())->resolve());
    }

    public function store(StoreCashboxRequest $request): JsonResponse
    {
        $cashbox = $this->cashboxService->create($request->validated());

        return ApiResponse::success(new CashboxResource($cashbox), 'Cashbox created', 201);
    }

    public function show(int $cashbox): JsonResponse
    {
        $cashboxModel = $this->cashboxService->findOrFail($cashbox);

        return ApiResponse::success(new CashboxResource($cashboxModel));
    }

    public function update(UpdateCashboxRequest $request, int $cashbox): JsonResponse
    {
        $cashboxModel = $this->cashboxService->findOrFail($cashbox);
        $cashboxModel = $this->cashboxService->update($cashboxModel, $request->validated());

        return ApiResponse::success(new CashboxResource($cashboxModel), 'Cashbox updated');
    }

    public function destroy(int $cashbox): JsonResponse
    {
        $cashboxModel = $this->cashboxService->findOrFail($cashbox);
        $this->cashboxService->delete($cashboxModel);

        return ApiResponse::success(null, 'Cashbox deleted');
    }

    public function transactions(Request $request, int $cashbox): JsonResponse
    {
        $cashboxModel = $this->cashboxService->findOrFail($cashbox);
        $perPage = max(1, min(100, $request->integer('per_page', 15)));
        $transactions = $this->cashboxService->transactions($cashboxModel, $perPage);

        return ApiResponse::paginated(
            $transactions,
            CashMovementResource::collection($transactions->items())->resolve()
        );
    }

    public function recalculate(int $cashbox): JsonResponse
    {
        $cashboxModel = $this->cashboxService->findOrFail($cashbox);
        $cashboxModel = $this->cashboxService->recalculate($cashboxModel);

        return ApiResponse::success(new CashboxResource($cashboxModel), 'Cashbox recalculated');
    }

    public function export(Request $request): StreamedResponse|Response
    {
        $rows = $this->cashboxService->exportRows([
            'search' => $request->query('search'),
            'branch_id' => $request->query('branch_id'),
            'is_active' => $request->query('is_active'),
        ]);
        $headers = ['المعرّف', 'الاسم', 'النوع', 'رقم الفرع', 'حساب الأستاذ', 'الرصيد الابتدائي', 'الرصيد الحالي', 'مقبوضات', 'مدفوعات', 'الحالة'];

        return TabularExport::download(
            $request->query('format'),
            'cashboxes',
            'الصناديق',
            $headers,
            $rows,
        );
    }

    public function dailySummary(Request $request): JsonResponse
    {
        $summary = $this->cashboxService->dailySummary([
            'cashbox_id' => $request->query('cashbox_id'),
            'branch_id' => $request->query('branch_id'),
            'date_from' => $request->query('date_from'),
            'date_to' => $request->query('date_to'),
        ]);

        return ApiResponse::success($summary);
    }

    public function receive(TreasuryOperationRequest $request): JsonResponse
    {
        $result = $this->treasuryOperations->receive($request->validated(), $request->user()?->id);

        return ApiResponse::success([
            'movement' => (new CashMovementResource($result['movement']))->resolve(),
            'journal' => (new JournalEntryResource($result['journal']))->resolve(),
        ], 'Receipt posted', 201);
    }

    public function pay(TreasuryOperationRequest $request): JsonResponse
    {
        $result = $this->treasuryOperations->pay($request->validated(), $request->user()?->id);

        return ApiResponse::success([
            'movement' => (new CashMovementResource($result['movement']))->resolve(),
            'journal' => (new JournalEntryResource($result['journal']))->resolve(),
        ], 'Payment posted', 201);
    }

    public function transfer(TreasuryTransferRequest $request): JsonResponse
    {
        $result = $this->treasuryOperations->transfer($request->validated(), $request->user()?->id);

        return ApiResponse::success([
            'from_movement' => (new CashMovementResource($result['from_movement']))->resolve(),
            'to_movement' => (new CashMovementResource($result['to_movement']))->resolve(),
            'journal' => (new JournalEntryResource($result['journal']))->resolve(),
        ], 'Transfer posted', 201);
    }
}
