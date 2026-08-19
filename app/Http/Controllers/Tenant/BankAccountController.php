<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Services\Tenant\BankAccountService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BankAccountController extends Controller
{
    public function __construct(
        private readonly BankAccountService $banks,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $this->filters($request);
        $page = $this->banks->paginate($filters, (int) $request->integer('per_page', 50));

        return ApiResponse::paginated($page, $page->items());
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:190'],
            'bank_name' => ['required', 'string', 'max:190'],
            'account_number' => ['required', 'string', 'max:64'],
            'iban' => ['nullable', 'string', 'max:64'],
            'currency' => ['nullable', 'string', 'max:8'],
            'branch_id' => ['nullable', 'integer'],
            'account_id' => ['required', 'integer'],
            'cashbox_id' => ['nullable', 'integer'],
            'opening_balance' => ['nullable', 'numeric'],
            'status' => ['nullable', 'in:active,inactive'],
            'notes' => ['nullable', 'string'],
        ]);

        $bank = $this->banks->create($data, $request->user()?->id);

        return ApiResponse::success($this->banks->present($bank), 'تم إنشاء الحساب البنكي', 201);
    }

    public function show(Request $request, int $bank): JsonResponse
    {
        $model = $this->banks->findOrFail($bank, $this->filters($request));

        return ApiResponse::success($this->banks->present($model));
    }

    public function update(Request $request, int $bank): JsonResponse
    {
        $model = $this->banks->findOrFail($bank, $this->filters($request));
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:190'],
            'bank_name' => ['sometimes', 'string', 'max:190'],
            'account_number' => ['nullable', 'string', 'max:64'],
            'iban' => ['nullable', 'string', 'max:64'],
            'currency' => ['nullable', 'string', 'max:8'],
            'branch_id' => ['nullable', 'integer'],
            'account_id' => ['sometimes', 'integer'],
            'cashbox_id' => ['nullable', 'integer'],
            'opening_balance' => ['nullable', 'numeric'],
            'status' => ['nullable', 'in:active,inactive'],
            'notes' => ['nullable', 'string'],
        ]);

        $updated = $this->banks->update($model, $data, $request->user()?->id);

        return ApiResponse::success($this->banks->present($updated), 'تم تحديث الحساب البنكي');
    }

    /**
     * @return array<string, mixed>
     */
    private function filters(Request $request): array
    {
        return [
            'search' => $request->query('search'),
            'status' => $request->query('status'),
            'branch_id' => $request->query('branch_id'),
            'date_to' => $request->query('date_to'),
            'user' => $request->user(),
        ];
    }
}
