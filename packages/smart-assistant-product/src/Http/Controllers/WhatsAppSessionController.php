<?php

declare(strict_types=1);

namespace DressnMore\SmartAssistantProduct\Http\Controllers;

use App\Models\Tenant\User;
use App\Services\Tenant\TenantContext;
use App\Support\ApiResponse;
use DressnMore\SmartAssistantProduct\Application\ChannelConnectionService;
use DressnMore\SmartAssistantProduct\Application\WhatsAppNumberService;
use DressnMore\SmartAssistantProduct\Domain\SocialChannelCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

/**
 * WhatsApp QR pairing session endpoints (Baileys gateway — not Meta Embedded Signup).
 * Compatibility layer: operates on the current employee's number.
 */
final class WhatsAppSessionController
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly ChannelConnectionService $channels,
        private readonly WhatsAppNumberService $numbers,
    ) {}

    public function connect(Request $request): JsonResponse
    {
        $tenant = $this->tenantContext->requireTenant();
        $user = $this->user($request);

        try {
            $row = $this->numbers->getOrCreateMine($tenant, $user);
            $state = $this->numbers->startSession($tenant, $user, (int) $row->id);
        } catch (Throwable $e) {
            return ApiResponse::error($e->getMessage(), 422);
        }

        return ApiResponse::success([
            'channel' => $state['number'] ?? null,
            'number' => $state['number'] ?? null,
            'session' => $state['session'] ?? null,
        ], 'تم بدء جلسة واتساب — امسح رمز QR');
    }

    public function qr(Request $request): JsonResponse
    {
        $tenant = $this->tenantContext->requireTenant();
        $user = $this->user($request);

        try {
            $row = $this->numbers->getOrCreateMine($tenant, $user);
            $qr = $this->numbers->qr($tenant, $user, (int) $row->id);
        } catch (Throwable $e) {
            return ApiResponse::error($e->getMessage(), 422);
        }

        return ApiResponse::success($qr);
    }

    public function status(Request $request): JsonResponse
    {
        $tenant = $this->tenantContext->requireTenant();
        $user = $this->user($request);

        try {
            $row = $this->numbers->getOrCreateMine($tenant, $user);
            $state = $this->numbers->status($tenant, $user, (int) $row->id);
        } catch (Throwable $e) {
            return ApiResponse::error($e->getMessage(), 422);
        }

        return ApiResponse::success($state);
    }

    public function send(Request $request): JsonResponse
    {
        $data = $request->validate([
            'to' => ['required', 'string', 'max:32'],
            'text' => ['required', 'string', 'max:1000'],
        ]);

        $tenant = $this->tenantContext->requireTenant();
        $user = $this->user($request);

        try {
            $row = $this->numbers->getOrCreateMine($tenant, $user);
            $this->channels->replyMessage((string) $tenant->id, SocialChannelCatalog::WHATSAPP, [
                'to' => $data['to'],
                'text' => $data['text'],
                'session_key' => $row->session_key,
            ]);
        } catch (Throwable $e) {
            return ApiResponse::error($e->getMessage(), 422);
        }

        return ApiResponse::success(['ok' => true], 'تم إرسال الرسالة');
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
