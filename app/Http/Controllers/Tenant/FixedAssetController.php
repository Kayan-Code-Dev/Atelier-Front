<?php

namespace App\Http\Controllers\Tenant;

use App\Accounting\AccountingAuditService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\Accounting\StoreFixedAssetRequest;
use App\Models\Tenant\FixedAsset;
use App\Services\Tenant\DepreciationService;
use App\Services\Tenant\FixedAssetService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FixedAssetController extends Controller
{
    public function __construct(
        private readonly FixedAssetService $assets,
        private readonly DepreciationService $depreciation,
        private readonly AccountingAuditService $audit,
    ) {}

    public function categories(): JsonResponse
    {
        return ApiResponse::success($this->assets->categories());
    }

    public function saveCategory(Request $request, ?int $category = null): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:190'],
            'code' => ['required', 'string', 'max:20'],
            'description' => ['nullable', 'string'],
            'asset_account_id' => ['required', 'integer'],
            'accumulated_depreciation_account_id' => ['required', 'integer'],
            'depreciation_expense_account_id' => ['required', 'integer'],
            'disposal_gain_loss_account_id' => ['required', 'integer'],
            'active' => ['nullable', 'boolean'],
        ]);

        $saved = $this->assets->saveCategory($data, $category);

        return ApiResponse::success($this->assets->presentCategory($saved), 'تم حفظ التصنيف');
    }

    public function index(Request $request): JsonResponse
    {
        $page = $this->assets->paginate($request->query(), (int) $request->integer('per_page', 20));
        $rows = collect($page->items())->map(fn (FixedAsset $asset) => $this->assets->present($asset))->all();

        return ApiResponse::paginated($page, $rows);
    }

    public function store(StoreFixedAssetRequest $request): JsonResponse
    {
        $asset = $this->assets->create($request->validated(), $request->user()?->id);

        return ApiResponse::success($this->detail($asset), 'تم إنشاء الأصل', 201);
    }

    public function show(int $asset): JsonResponse
    {
        return ApiResponse::success($this->detail($this->assets->findOrFail($asset)));
    }

    public function update(Request $request, int $asset): JsonResponse
    {
        $model = $this->assets->findOrFail($asset);
        $updated = $this->assets->update($model, $request->all(), $request->user()?->id);

        return ApiResponse::success($this->detail($updated), 'تم تحديث الأصل');
    }

    public function previewDisposal(Request $request, int $asset): JsonResponse
    {
        $model = $this->assets->findOrFail($asset);

        return ApiResponse::success($this->assets->previewDisposal($model, $request->all()));
    }

    public function dispose(Request $request, int $asset): JsonResponse
    {
        $model = $this->assets->findOrFail($asset);
        $updated = $this->assets->dispose($model, $request->all(), $request->user()?->id);

        return ApiResponse::success($this->detail($updated), 'تم التصرف في الأصل');
    }

    public function transfer(Request $request, int $asset): JsonResponse
    {
        $model = $this->assets->findOrFail($asset);
        $updated = $this->assets->transfer($model, $request->all(), $request->user()?->id);

        return ApiResponse::success($this->detail($updated), 'تم نقل الأصل');
    }

    public function depreciationPreview(Request $request): JsonResponse
    {
        $period = (string) $request->query('period', now()->format('Y-m'));

        return ApiResponse::success($this->depreciation->preview(
            $period,
            $request->query('branch_id') ? (int) $request->query('branch_id') : null,
        ));
    }

    public function depreciationPost(Request $request): JsonResponse
    {
        $data = $request->validate([
            'period' => ['required', 'string'],
            'branch_id' => ['nullable', 'integer'],
        ]);

        return ApiResponse::success(
            $this->depreciation->post($data['period'], isset($data['branch_id']) ? (int) $data['branch_id'] : null, $request->user()?->id),
            'تم ترحيل الإهلاك'
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function detail(FixedAsset $asset): array
    {
        $payload = $this->assets->present($asset);
        $payload['depreciation'] = $this->depreciation->scheduleFor($asset);
        $payload['transactions'] = $asset->transactions->map(fn ($row) => [
            'id' => $row->id,
            'type' => $row->type,
            'occurred_at' => $row->occurred_at?->toDateString(),
            'amount' => (float) $row->amount,
            'journal_entry_id' => $row->journal_entry_id,
            'payload' => $row->payload,
        ])->all();
        $payload['transfers'] = $asset->transfers->map(fn ($row) => [
            'id' => $row->id,
            'transferred_at' => $row->transferred_at?->toDateString(),
            'from_branch_id' => $row->from_branch_id,
            'to_branch_id' => $row->to_branch_id,
            'from_location' => $row->from_location,
            'to_location' => $row->to_location,
            'reason' => $row->reason,
            'notes' => $row->notes,
        ])->all();
        $payload['audit'] = $this->audit->timeline('fixed_asset', $asset->id);

        return $payload;
    }
}
