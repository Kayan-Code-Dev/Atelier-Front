<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Services\Tenant\AccountingPeriodService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AccountingPeriodController extends Controller
{
    public function __construct(private readonly AccountingPeriodService $periods) {}

    public function index(Request $request): JsonResponse
    {
        $year = $request->query('year');

        return ApiResponse::success($this->periods->list($year !== null && $year !== '' ? (int) $year : null));
    }

    public function show(int $period): JsonResponse
    {
        return ApiResponse::success($this->periods->present($this->periods->findOrFail($period)));
    }

    public function validateClose(int $period): JsonResponse
    {
        $model = $this->periods->findOrFail($period);

        return ApiResponse::success($this->periods->validateClose($model));
    }

    public function close(Request $request, int $period): JsonResponse
    {
        $model = $this->periods->findOrFail($period);
        $validation = $this->periods->validateClose($model);
        if (! $validation['can_close']) {
            return ApiResponse::error('لا يمكن إغلاق الفترة قبل معالجة الفحوصات الفاشلة.', 422, $validation);
        }

        try {
            return ApiResponse::success($this->periods->close(
                $model,
                $request->user()?->id,
                $request->boolean('confirm')
            ));
        } catch (ValidationException $exception) {
            return ApiResponse::error($exception->getMessage(), 422, $exception->errors());
        }
    }

    public function reopen(Request $request, int $period): JsonResponse
    {
        $data = $request->validate([
            'reason' => ['required', 'string', 'min:8'],
        ]);

        return ApiResponse::success($this->periods->reopen(
            $this->periods->findOrFail($period),
            $request->user()?->id,
            $data['reason']
        ));
    }

    public function lock(Request $request, int $period): JsonResponse
    {
        return ApiResponse::success($this->periods->lock(
            $this->periods->findOrFail($period),
            $request->user()?->id
        ));
    }
}
