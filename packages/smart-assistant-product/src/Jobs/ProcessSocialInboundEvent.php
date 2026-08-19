<?php

declare(strict_types=1);

namespace DressnMore\SmartAssistantProduct\Jobs;

use DressnMore\SmartAssistantProduct\Application\MetaSocialAutoReplyService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class ProcessSocialInboundEvent implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * @param array<string, mixed> $inbound
     */
    public function __construct(
        public readonly string $tenantId,
        public readonly string $channelType,
        public readonly string $kind,
        public readonly array $inbound,
    ) {
        $this->onQueue((string) config('smart-assistant-product.queue', 'intelligence'));
    }

    public function handle(MetaSocialAutoReplyService $autoReply): void
    {
        if ($this->kind === 'comment') {
            $autoReply->handleComment($this->tenantId, $this->channelType, $this->inbound);

            return;
        }

        $autoReply->handleMessage($this->tenantId, $this->channelType, $this->inbound);
    }
}
