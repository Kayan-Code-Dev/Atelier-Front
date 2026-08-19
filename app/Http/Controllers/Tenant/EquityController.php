<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Services\Tenant\EquityOperationService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EquityController extends Controller
{
    public function __construct(private readonly EquityOperationService $equity) {}

    public function index(Request $request): JsonResponse
    {
        $page = $this->equity->paginate($request->query(), (int) $request->integer('per_page', 20));
        $rows = collect($page->items())->map(fn ($row) => $this->equity->present($row))->all();

        return ApiResponse::paginated($page, $rows);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'type' => ['required', 'in:contribution,drawing'],
            'owner_name' => ['required', 'string', 'max:190'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'occurred_at' => ['required', 'date'],
            'cash_account_id' => ['required', 'integer'],
            'equity_account_id' => ['nullable', 'integer'],
            'branch_id' => ['nullable', 'integer'],
            'description' => ['nullable', 'string'],
            'attachments' => ['nullable', 'array'],
        ]);

        $operation = $this->equity->create($data, $request->user()?->id);

        return ApiResponse::success($this->equity->present($operation), 'تم إنشاء حركة الملكية', 201);
    }
}
