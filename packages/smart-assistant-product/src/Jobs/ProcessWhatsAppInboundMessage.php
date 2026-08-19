<?php

declare(strict_types=1);

namespace DressnMore\SmartAssistantProduct\Jobs;

use DressnMore\SmartAssistantProduct\Application\WhatsAppAutoReplyService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class ProcessWhatsAppInboundMessage implements ShouldQueue
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
        public readonly array $inbound,
    ) {
        $this->onQueue((string) config('smart-assistant-product.queue', 'intelligence'));
    }

    public function handle(WhatsAppAutoReplyService $autoReply): void
    {
        $autoReply->handle($this->tenantId, $this->inbound);
    }
}
