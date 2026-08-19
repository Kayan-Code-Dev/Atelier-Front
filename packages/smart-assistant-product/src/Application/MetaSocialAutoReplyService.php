<?php

declare(strict_types=1);

namespace DressnMore\SmartAssistantProduct\Application;

use App\Models\Central\Tenant;
use DressnMore\SmartAssistantProduct\Domain\ChannelConnectionStoreInterface;
use DressnMore\SmartAssistantProduct\Domain\SocialChannelCatalog;
use DressnMore\SmartAssistantProduct\Models\SmartAssistantInboundMessage;
use Illuminate\Support\Facades\Log;
use Throwable;

final class MetaSocialAutoReplyService
{
    public function __construct(
        private readonly ChannelConnectionService $channels,
        private readonly ChannelConnectionStoreInterface $store,
        private readonly AiQuotaService $quotaService,
    ) {}

    /**
     * @param array<string, mixed> $inbound
     */
    public function handleMessage(string $tenantId, string $channelType, array $inbound): void
    {
        $creds = $this->store->credentials($tenantId, $channelType);
        if ($creds === null || ! ($creds['auto_reply_enabled'] ?? false)) {
            return;
        }
        $mode = (string) ($creds['auto_reply_mode'] ?? 'template');
        if ($mode === 'off') {
            return;
        }

        $from = (string) ($inbound['from'] ?? '');
        $text = trim((string) ($inbound['text'] ?? ''));
        $messageId = (string) ($inbound['id'] ?? '');
        if ($from === '') {
            return;
        }

        $tenant = Tenant::query()->find((int) $tenantId);
        if ($tenant === null || ! $this->quotaService->canConsume($tenant)) {
            return;
        }

        $reply = $this->templateReply($channelType, $text);
        try {
            $this->channels->replyMessage($tenantId, $channelType, [
                'to' => $from,
                'text' => $reply,
            ]);
            $this->quotaService->recordMessage($tenant, null, null, ['channel' => $channelType]);
            $this->markInbound($channelType, $messageId, 'replied');
        } catch (Throwable $e) {
            Log::warning('social.auto_reply_message_failed', [
                'channel' => $channelType,
                'tenant_id' => $tenantId,
                'error' => $e->getMessage(),
            ]);
            $this->markInbound($channelType, $messageId, 'failed');
        }
    }

    /**
     * @param array<string, mixed> $inbound
     */
    public function handleComment(string $tenantId, string $channelType, array $inbound): void
    {
        $creds = $this->store->credentials($tenantId, $channelType);
        if ($creds === null || ! ($creds['auto_reply_enabled'] ?? false)) {
            return;
        }
        $mode = (string) ($creds['auto_reply_mode'] ?? 'template');
        if ($mode === 'off') {
            return;
        }

        $commentId = (string) ($inbound['id'] ?? '');
        $text = trim((string) ($inbound['text'] ?? ''));
        if ($commentId === '') {
            return;
        }

        $tenant = Tenant::query()->find((int) $tenantId);
        if ($tenant === null || ! $this->quotaService->canConsume($tenant)) {
            return;
        }

        $reply = $this->templateReply($channelType, $text);
        try {
            $this->channels->replyComment($tenantId, $channelType, [
                'comment_id' => $commentId,
                'text' => $reply,
            ]);
            $this->quotaService->recordMessage($tenant, null, null, ['channel' => $channelType, 'kind' => 'comment']);
        } catch (Throwable $e) {
            Log::warning('social.auto_reply_comment_failed', [
                'channel' => $channelType,
                'tenant_id' => $tenantId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function templateReply(string $channelType, string $inboundText): string
    {
        $label = match ($channelType) {
            SocialChannelCatalog::FACEBOOK => 'فيسبوك',
            SocialChannelCatalog::INSTAGRAM => 'إنستغرام',
            default => $channelType,
        };

        $template = (string) config(
            'smart-assistant-product.messenger.auto_reply_template',
            "مرحباً 👋\nتم استلام رسالتك عبر المساعد الذكي لـ DressnMore ({$label}).\nسنعاود الرد عليك في أقرب وقت."
        );

        if ($inboundText !== '') {
            return $template."\n\n—\nرسالتك: ".$inboundText;
        }

        return $template;
    }

    private function markInbound(string $channelType, string $messageId, string $status): void
    {
        if ($messageId === '') {
            return;
        }
        $update = ['status' => $status];
        if ($status === 'replied') {
            $update['replied_at'] = now();
        }
        SmartAssistantInboundMessage::query()
            ->where('channel_type', $channelType)
            ->where('external_message_id', $messageId)
            ->update($update);
    }
}
