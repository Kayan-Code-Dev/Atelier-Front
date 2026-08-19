<?php

declare(strict_types=1);

namespace DressnMore\SmartAssistantProduct\Application;

use App\Models\Central\Tenant;
use App\Support\PlanFeatureGate;
use DressnMore\Aos\Response\Application\EndToEndAiOrchestrator;
use DressnMore\SmartAssistantProduct\SalesIntelligence\Models\IntelligenceConversation;
use DressnMore\SmartAssistantProduct\SalesIntelligence\Orchestrator\Application\AgentOrchestrator;
use DressnMore\SmartAssistantProduct\Domain\ChannelConnectionStoreInterface;
use DressnMore\SmartAssistantProduct\Domain\SocialChannelCatalog;
use DressnMore\SmartAssistantProduct\Models\SmartAssistantInboundMessage;
use DressnMore\SmartAssistantProduct\Models\SmartAssistantWhatsAppConversation;
use Illuminate\Support\Facades\Log;
use Throwable;

final class WhatsAppAutoReplyService
{
    public function __construct(
        private readonly ChannelConnectionService $channels,
        private readonly ChannelConnectionStoreInterface $store,
        private readonly PlanFeatureGate $planFeatureGate,
        private readonly WhatsAppSalesAgentService $salesAgent,
        private readonly AiQuotaService $quotaService,
        private readonly WhatsAppNumberService $numbers,
    ) {}

    /**
     * @param array<string, mixed> $inbound
     */
    public function handle(string $tenantId, array $inbound): void
    {
        // state() works for both Meta and whatsapp_web (QR) connections —
        // credentials() is Meta-shaped and returns null for QR sessions.
        $sessionRaw = (string) ($inbound['session_key'] ?? $inbound['raw']['session_key'] ?? $tenantId);
        $connection = $this->numbers->findBySessionKey($sessionRaw);
        $state = $this->store->state($tenantId, SocialChannelCatalog::WHATSAPP);
        if ($connection !== null) {
            if (! $connection->auto_reply_enabled) {
                return;
            }
            $state['auto_reply_enabled'] = true;
            $state['auto_reply_mode'] = (string) ($connection->auto_reply_mode ?: ($state['auto_reply_mode'] ?? ''));
            $state['status'] = 'connected';
        } elseif (($state['status'] ?? 'disconnected') !== 'connected' || ! ($state['auto_reply_enabled'] ?? false)) {
            return;
        }

        $mode = (string) ($state['auto_reply_mode'] ?? '');
        if ($mode === '' || $mode === 'off') {
            $mode = (string) config('smart-assistant-product.whatsapp.auto_reply_mode', 'template');
        }
        if ($mode === 'off') {
            return;
        }

        $tenant = Tenant::query()->find((int) $tenantId);
        if ($tenant === null) {
            return;
        }

        $planKey = (string) config('smart-assistant-product.plan_feature_auto_reply', 'smart_assistant.auto_reply');
        // Allow template replies when channel connected even if plan auto_reply is off,
        // but planner mode requires the plan flag.
        if ($mode === 'planner' && ! $this->planFeatureGate->isEnabled($tenant, $planKey)) {
            $mode = 'template';
        }

        $from = (string) ($inbound['from'] ?? '');
        $text = trim((string) ($inbound['text'] ?? ''));
        $messageId = (string) ($inbound['id'] ?? '');
        if ($from === '') {
            return;
        }

        $raw = is_array($inbound['raw'] ?? null) ? $inbound['raw'] : [];
        $fromPhone = (string) ($inbound['from_phone'] ?? $raw['from_phone'] ?? '');

        // ── Platform channel: the REAL runtime path goes through the
        // Agent Orchestrator + Business Brain. Tenant ateliers keep their
        // existing flow untouched below.
        $platformTenantId = (string) config('smart-assistant-product.whatsapp_web.platform_tenant_id', '');
        if ($mode === 'sales' && $platformTenantId !== '' && (int) $platformTenantId === (int) $tenant->id) {
            $this->handleOrchestratedReply((string) $tenant->id, $from, $text, $messageId, (string) ($inbound['raw']['push_name'] ?? $inbound['push_name'] ?? ''));

            return;
        }

        if (! $this->quotaService->canConsume($tenant)) {
            Log::info('whatsapp.auto_reply.quota_exhausted', ['tenant_id' => $tenant->id]);

            return;
        }

        if ($mode === 'sales') {
            $reply = $this->salesAgent->buildReply($tenant, $from, $text, $fromPhone !== '' ? $fromPhone : null, [
                'agent_name' => $connection?->assistant_name,
                'department_name' => $connection?->department_name,
            ]);
        } else {
            $reply = $this->buildReply($tenantId, $mode, $text);
        }
        if ($reply === '') {
            return;
        }

        try {
            $this->channels->replyMessage($tenantId, SocialChannelCatalog::WHATSAPP, [
                'to' => $from,
                'text' => $reply,
                'session_key' => $connection?->session_key ?: $sessionRaw,
            ]);
            $this->quotaService->recordMessage($tenant, null, null, ['channel' => 'whatsapp']);

            if ($messageId !== '') {
                SmartAssistantInboundMessage::query()
                    ->where('channel_type', SocialChannelCatalog::WHATSAPP)
                    ->where('external_message_id', $messageId)
                    ->update([
                        'status' => 'replied',
                        'replied_at' => now(),
                    ]);
            }
        } catch (Throwable $e) {
            Log::warning('whatsapp.auto_reply_failed', [
                'tenant_id' => $tenantId,
                'error' => $e->getMessage(),
            ]);
            if ($messageId !== '') {
                SmartAssistantInboundMessage::query()
                    ->where('channel_type', SocialChannelCatalog::WHATSAPP)
                    ->where('external_message_id', $messageId)
                    ->update(['status' => 'failed']);
            }
        }
    }

    private function buildReply(string $tenantId, string $mode, string $inboundText): string
    {
        if ($mode === 'planner' && class_exists(EndToEndAiOrchestrator::class) && app()->bound(EndToEndAiOrchestrator::class)) {
            try {
                /** @var EndToEndAiOrchestrator $orchestrator */
                $orchestrator = app(EndToEndAiOrchestrator::class);
                $result = $orchestrator->handle(
                    $inboundText !== '' ? $inboundText : 'مرحبا',
                    $tenantId,
                    'ar',
                );
                $text = trim((string) ($result->response()->message() ?? ''));
                if ($text !== '') {
                    return mb_substr($text, 0, 3500);
                }
            } catch (Throwable $e) {
                Log::warning('whatsapp.planner_reply_failed', ['error' => $e->getMessage()]);
            }
        }

        $template = (string) config(
            'smart-assistant-product.whatsapp.auto_reply_template',
            "مرحباً 👋\nتم استلام رسالتك عبر المساعد الذكي لـ DressnMore.\nسنعاود الرد عليك في أقرب وقت."
        );

        if ($inboundText !== '') {
            return $template."\n\n—\nرسالتك: ".$inboundText;
        }

        return $template;
    }

    // ------------------------------------------------------------------
    // Platform channel: Agent Orchestrator + Business Brain runtime path.
    // The Orchestrator DECIDES; the existing transport SENDS.
    // ------------------------------------------------------------------
    private function handleOrchestratedReply(string $tenantId, string $from, string $text, string $messageId, string $pushName = ''): void
    {
        $connectionTenantId = (int) $tenantId;
        $conversation = $this->findPlatformConversation($connectionTenantId, $from);
        if ($conversation === null) {
            // Every WhatsApp thread must have a persistent conversation row so
            // handoffs, ownership and audit trails never lose the context.
            $conversation = IntelligenceConversation::query()->create([
                'customer_id' => $from,
                'customer_name' => $from,
                'channel' => 'whatsapp',
                'handler' => 'ai',
                'status' => 'open',
                'assigned_agent' => 'sales',
                'last_activity_at' => now(),
                'meta' => ['tenant_id' => $connectionTenantId],
            ]);
        } elseif ((int) ($conversation->meta['tenant_id'] ?? 0) !== $connectionTenantId) {
            $meta = (array) ($conversation->meta ?? []);
            $meta['tenant_id'] = $connectionTenantId;
            $conversation->meta = $meta;
            $conversation->save();
        }

        // Keep a HUMAN-readable customer name: prefer the WhatsApp profile
        // name (push_name) or a name captured from the conversation itself —
        // never leave a raw JID as the display name.
        $knownName = trim($pushName);
        if ($knownName === '') {
            $knownName = (string) (\DressnMore\SmartAssistantProduct\SalesIntelligence\Orchestrator\Memory\MemoryManager::extractPersonName($text) ?? '');
        }
        if ($knownName !== '' && str_contains((string) $conversation->customer_name, '@')) {
            $conversation->customer_name = mb_substr($knownName, 0, 190);
            $conversation->save();
            app(\DressnMore\SmartAssistantProduct\SalesIntelligence\Orchestrator\Memory\MemoryManager::class)
                ->remember($from, 'customer.name', $knownName, 'whatsapp_profile', 0.9, $connectionTenantId);
        }

        try {
            $t0 = microtime(true);
            $result = app(AgentOrchestrator::class)->handle($from, $text !== '' ? $text : 'مرحبا', $conversation, $connectionTenantId);
            $reply = (string) ($result['reply'] ?? '');
            $brain = $result['brain'] ?? null;

            Log::info('[AI][ORCHESTRATOR]', [
                'conversation_id' => $conversation?->id,
                'stage' => 'orchestrator',
                'status' => 'ok',
                'duration_ms' => (int) ((microtime(true) - $t0) * 1000),
                'result_summary' => $brain !== null ? json_encode($brain->snapshot(), JSON_UNESCAPED_UNICODE) : 'no-brain',
            ]);

            // Human takeover / explicit escalation: AI stays silent.
            if ($reply === '' || ($result['escalated'] ?? false)) {
                $this->markInbound($messageId, 'replied');

                return;
            }
        } catch (Throwable $e) {
            // Explicit, observable fallback — never a silent bypass.
            Log::warning('[AI][FALLBACK]', [
                'conversation_id' => $conversation?->id,
                'reason' => $e->getMessage(),
            ]);
            $tenant = Tenant::query()->find((int) $tenantId);
            $reply = $tenant !== null ? $this->salesAgent->buildReply($tenant, $from, $text) : '';
            if ($reply === '') {
                $reply = (string) config(
                    'smart-assistant-product.whatsapp.auto_reply_template',
                    "مرحباً 👋\nتم استلام رسالتك وسنعاود الرد عليك في أقرب وقت."
                );
            }
        }

        try {
            $this->channels->replyMessage($tenantId, SocialChannelCatalog::WHATSAPP, [
                'to' => $from,
                'text' => mb_substr($reply, 0, 3500),
            ]);
            $this->markInbound($messageId, 'replied');
            $this->persistPlatformHistory($tenantId, $from, $text, $reply);
        } catch (Throwable $e) {
            Log::warning('whatsapp.auto_reply_failed', [
                'tenant_id' => $tenantId,
                'error' => $e->getMessage(),
            ]);
            $this->markInbound($messageId, 'failed');
        }
    }

    private function findPlatformConversation(int $tenantId, string $from): ?IntelligenceConversation
    {
        $scoped = IntelligenceConversation::query()
            ->where('customer_id', $from)
            ->where('channel', 'whatsapp')
            ->where('meta->tenant_id', $tenantId)
            ->latest('id')
            ->first();
        if ($scoped !== null) {
            return $scoped;
        }

        $platformId = (int) config('smart-assistant-product.whatsapp_web.platform_tenant_id', 0);
        if ($platformId > 0 && $platformId === $tenantId) {
            return IntelligenceConversation::query()
                ->where('customer_id', $from)
                ->where('channel', 'whatsapp')
                ->where(function ($q): void {
                    $q->whereNull('meta->tenant_id')->orWhere('meta->tenant_id', '');
                })
                ->latest('id')
                ->first();
        }

        return null;
    }

    private function persistPlatformHistory(string $tenantId, string $from, string $userText, string $reply): void
    {
        try {
            $convo = SmartAssistantWhatsAppConversation::query()->firstOrNew([
                'tenant_id' => (int) $tenantId,
                'phone' => $from,
            ]);
            if (($convo->handler ?? '') === '') {
                $convo->handler = 'ai';
            }
            $convo->pushHistory('user', $userText !== '' ? $userText : 'مرحبا');
            if (trim($reply) !== '') {
                $convo->pushHistory('assistant', $reply);
            }
            $convo->save();
        } catch (Throwable $e) {
            Log::warning('[AI][HISTORY] persist_failed', ['error' => $e->getMessage()]);
        }
    }

    private function markInbound(string $messageId, string $status): void
    {
        if ($messageId === '') {
            return;
        }

        SmartAssistantInboundMessage::query()
            ->where('channel_type', SocialChannelCatalog::WHATSAPP)
            ->where('external_message_id', $messageId)
            ->update([
                'status' => $status,
                'replied_at' => $status === 'replied' ? now() : null,
            ]);
    }
}