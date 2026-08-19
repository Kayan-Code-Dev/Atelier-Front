<?php

declare(strict_types=1);

namespace DressnMore\Aos\Communication\Domain\Routing;

use DressnMore\Aos\Communication\Domain\Message\NormalizedMessage;

final class ConversationRouter
{
    public function route(NormalizedMessage $message): string
    {
        if ($message->conversationId() !== '') {
            return $message->conversationId();
        }

        return hash('sha1', $message->channel()->value.'|'.$message->sender().'|'.$message->receiver());
    }
}
