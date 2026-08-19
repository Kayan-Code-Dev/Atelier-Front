<?php

declare(strict_types=1);

namespace DressnMore\SmartAssistant\Contracts\Conversation;

use DressnMore\SmartAssistant\Domain\Conversation\Conversation;

interface ConversationInterface
{
    public function identity(): Conversation;
}
