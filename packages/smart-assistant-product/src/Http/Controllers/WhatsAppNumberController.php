<?php

declare(strict_types=1);

namespace DressnMore\SmartAssistantProduct\Http\Controllers;

use App\Models\Tenant\User;
use App\Services\Tenant\TenantContext;
use App\Support\ApiResponse;
use DressnMore\SmartAssistantProduct\Application\WhatsAppNumberService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

final class WhatsAppNumberController
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly WhatsAppNumberService $numbers,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = $this->user($request);

        return ApiResponse::success([
            'numbers' => $this->numbers->listFor($this->tenantContext->requireTenant(), $user),
            'can_manage_all' => $this->numbers->canManageAll($user),
            'departments' => $this->numbers->departments(),
        ]);
    }

    public function departments(): JsonResponse
    {
        return ApiResponse::success(['departments' => $this->numbers->departments()]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'assistant_name' => ['nullable', 'string', 'max:120'],
            'department_id' => ['nullable', 'integer'],
            'department_name' => ['nullable', 'string', 'max:120'],
            'user_id' => ['nullable', 'integer'],
        ]);

        try {
            $row = $this->numbers->createFor($this->tenantContext->requireTenant(), $this->user($request), $data);
        } catch (Throwable $e) {
            return ApiResponse::error($e->getMessage(), 422);
        }

        return ApiResponse::success($row, 'تم إنشاء رقم المساعد');
    }

    public function update(Request $request, int $number): JsonResponse
    {
        $data = $request->validate([
            'assistant_name' => ['nullable', 'string', 'max:120'],
            'department_id' => ['nullable', 'integer'],
            'department_name' => ['nullable', 'string', 'max:120'],
            'auto_reply_enabled' => ['nullable', 'boolean'],
            'auto_reply_mode' => ['nullable', 'string', 'in:template,planner,sales,off'],
        ]);

        try {
            $row = $this->numbers->updateFor($this->tenantContext->requireTenant(), $this->user($request), $number, $data);
        } catch (Throwable $e) {
            return ApiResponse::error($e->getMessage(), 422);
        }

        return ApiResponse::success($row, 'تم حفظ إعدادات المساعد');
    }

    public function connect(Request $request, int $number): JsonResponse
    {
        try {
            $state = $this->numbers->startSession($this->tenantContext->requireTenant(), $this->user($request), $number);
        } catch (Throwable $e) {
            return ApiResponse::error($e->getMessage(), 422);
        }

        return ApiResponse::success($state, 'تم بدء جلسة واتساب — امسح رمز QR');
    }

    public function qr(Request $request, int $number): JsonResponse
    {
        try {
            $qr = $this->numbers->qr($this->tenantContext->requireTenant(), $this->user($request), $number);
        } catch (Throwable $e) {
            return ApiResponse::error($e->getMessage(), 422);
        }

        return ApiResponse::success($qr);
    }

    public function status(Request $request, int $number): JsonResponse
    {
        try {
            $state = $this->numbers->status($this->tenantContext->requireTenant(), $this->user($request), $number);
        } catch (Throwable $e) {
            return ApiResponse::error($e->getMessage(), 422);
        }

        return ApiResponse::success($state);
    }

    public function disconnect(Request $request, int $number): JsonResponse
    {
        try {
            $row = $this->numbers->disconnect($this->tenantContext->requireTenant(), $this->user($request), $number);
        } catch (Throwable $e) {
            return ApiResponse::error($e->getMessage(), 422);
        }

        return ApiResponse::success($row, 'تم فصل واتساب');
    }

    private function user(Request $request): User
    {
        $user = $request->user();
        if (! $user instanceof User) {
            abort(401);
        }

        return $user;
    }
}
