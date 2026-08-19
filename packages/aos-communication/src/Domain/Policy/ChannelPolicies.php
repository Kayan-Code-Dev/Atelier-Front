<?php

declare(strict_types=1);

namespace DressnMore\Aos\Communication\Domain\Policy;

use DressnMore\Aos\Communication\Domain\Message\NormalizedMessage;

final class ChannelPolicies
{
    public function __construct(
        private readonly int $maxTextLength = 4000,
        private readonly int $maxAttachments = 10,
    ) {}

    public function allows(NormalizedMessage $message): bool
    {
        return mb_strlen($message->text()) <= $this->maxTextLength
            && count($message->attachments()) <= $this->maxAttachments;
    }

    public function retryAttempts(): int
    {
        return 2;
    }
}
