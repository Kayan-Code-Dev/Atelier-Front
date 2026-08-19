<?php

declare(strict_types=1);

namespace DressnMore\SmartAssistantProduct\Application;

use App\Models\Central\Tenant;
use App\Models\Tenant\Customer;
use App\Models\Tenant\Invoice;
use DressnMore\SmartAssistantProduct\Infrastructure\WhatsAppWeb\WhatsAppGatewayClient;
use DressnMore\SmartAssistantProduct\Models\SmartAssistantWhatsAppConversation;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

/**
 * Sends tenant-scoped WhatsApp outbound (QR/Baileys session of THIS tenant only).
 * Never uses the platform session.
 */
final class TenantWhatsAppOutbound
{
    public function __construct(
        private readonly WhatsAppGatewayClient $gateway,
    ) {}

    public function isPlatformTenant(int $tenantId): bool
    {
        $platformId = (int) config('smart-assistant-product.whatsapp_web.platform_tenant_id', 0);

        return $platformId > 0 && $tenantId === $platformId;
    }

    public function sessionConnected(string $tenantId): bool
    {
        $key = app(WhatsAppNumberService::class)->resolveConnectedSessionKey((int) $tenantId);
        if ($key === null) {
            return false;
        }
        try {
            $state = $this->gateway->status($key);

            return (string) ($state['session']['status'] ?? $state['status'] ?? '') === 'connected';
        } catch (Throwable) {
            return false;
        }
    }

    public function resolveRecipient(Tenant $tenant, Invoice $invoice, ?string $preferredTo = null): ?string
    {
        if (filled($preferredTo)) {
            return (string) $preferredTo;
        }

        $invoice->loadMissing('customer');
        $customer = $invoice->customer;

        $convo = null;
        if ($customer instanceof Customer && $customer->id) {
            $convo = SmartAssistantWhatsAppConversation::query()
                ->where('tenant_id', $tenant->id)
                ->where('customer_id', $customer->id)
                ->orderByDesc('last_activity_at')
                ->first();
        }

        if ($convo instanceof SmartAssistantWhatsAppConversation && filled($convo->phone)) {
            return (string) $convo->phone;
        }

        $phone = $customer instanceof Customer
            ? (string) ($customer->whatsapp ?: $customer->phone ?: '')
            : '';
        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        if ($digits === '') {
            return null;
        }

        $match = SmartAssistantWhatsAppConversation::query()
            ->where('tenant_id', $tenant->id)
            ->where(function ($q) use ($digits): void {
                $q->where('phone', $digits)
                    ->orWhere('phone', 'like', '%'.$digits.'%')
                    ->orWhere('phone', 'like', '%'.substr($digits, -9).'%');
            })
            ->orderByDesc('last_activity_at')
            ->first();

        return $match instanceof SmartAssistantWhatsAppConversation && filled($match->phone)
            ? (string) $match->phone
            : $digits;
    }

    public function sendText(string $tenantId, string $to, string $text): void
    {
        if ($this->isPlatformTenant((int) $tenantId)) {
            throw new RuntimeException('refusing to send atelier WhatsApp via platform session');
        }
        $this->gateway->send($this->sessionKeyFor((int) $tenantId), $to, $text);
    }

    public function sendDocument(string $tenantId, string $to, string $filename, string $bytes, ?string $caption = null): void
    {
        if ($this->isPlatformTenant((int) $tenantId)) {
            throw new RuntimeException('refusing to send atelier WhatsApp via platform session');
        }
        $this->gateway->sendDocument($this->sessionKeyFor((int) $tenantId), $to, $filename, $bytes, $caption);
    }

    private function sessionKeyFor(int $tenantId): string
    {
        $userId = auth()->id();
        $key = app(WhatsAppNumberService::class)->resolveConnectedSessionKey(
            $tenantId,
            is_numeric($userId) ? (int) $userId : null,
        );
        if ($key === null || $key === '') {
            throw new RuntimeException('لا توجد جلسة واتساب مربوطة');
        }

        return $key;
    }

    public function safeSendText(string $tenantId, string $to, string $text): bool
    {
        try {
            $this->sendText($tenantId, $to, $text);

            return true;
        } catch (Throwable $e) {
            Log::warning('whatsapp.tenant_outbound.text_failed', [
                'tenant_id' => $tenantId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
