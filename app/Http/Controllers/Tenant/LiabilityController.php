<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Services\Tenant\LiabilityService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LiabilityController extends Controller
{
    public function __construct(private readonly LiabilityService $liabilities) {}

    public function index(Request $request): JsonResponse
    {
        $page = $this->liabilities->paginate($request->query(), (int) $request->integer('per_page', 20));
        $rows = collect($page->items())->map(fn ($row) => $this->liabilities->present($row))->all();

        return ApiResponse::paginated($page, $rows);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'type' => ['nullable', 'in:loan,supplier_payable,other'],
            'lender' => ['nullable', 'string', 'max:190'],
            'number' => ['nullable', 'string', 'max:50'],
            'name' => ['nullable', 'string', 'max:190'],
            'principal' => ['required', 'numeric', 'min:0.01'],
            'start_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date'],
            'liability_account_id' => ['required', 'integer'],
            'cash_account_id' => ['required', 'integer'],
            'branch_id' => ['nullable', 'integer'],
            'notes' => ['nullable', 'string'],
        ]);

        $liability = $this->liabilities->create($data, $request->user()?->id);

        return ApiResponse::success($this->liabilities->present($liability), 'تم تسجيل الالتزام', 201);
    }

    public function show(int $liability): JsonResponse
    {
        return ApiResponse::success($this->liabilities->present($this->liabilities->findOrFail($liability)));
    }

    public function settle(Request $request, int $liability): JsonResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'settled_at' => ['required', 'date'],
            'cash_account_id' => ['nullable', 'integer'],
            'reference' => ['nullable', 'string', 'max:80'],
            'notes' => ['nullable', 'string'],
        ]);

        $updated = $this->liabilities->settle($this->liabilities->findOrFail($liability), $data, $request->user()?->id);

        return ApiResponse::success($this->liabilities->present($updated), 'تم سداد الالتزام');
    }
}
