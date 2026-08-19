<?php

declare(strict_types=1);

namespace DressnMore\Aos\Response\Application;

use DressnMore\Aos\Response\Domain\Response\FinalAiResponse;

/**
 * Shapes FinalAiResponse for conversation/UI delivery (no channel send).
 */
final class ConversationReplyGenerator
{
    /**
     * @return array{role:string,content:string,status:string,locale:string,meta:array<string,mixed>}
     */
    public function forConversation(FinalAiResponse $response): array
    {
        return [
            'role' => 'assistant',
            'content' => $response->message(),
            'status' => $response->status()->value,
            'locale' => $response->locale(),
            'meta' => [
                'sections' => $response->sections(),
                'planId' => $response->planId(),
                'correlationId' => $response->correlationId(),
                'data' => $response->data(),
            ],
        ];
    }
}
