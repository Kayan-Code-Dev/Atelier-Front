<?php

declare(strict_types=1);

namespace DressnMore\Aos\TenantAi\Contracts;

use DressnMore\Aos\TenantAi\Domain\Message\AiMessage;

interface MessageProviderInterface
{
    public function append(AiMessage $message): AiMessage;

    /**
     * @return list<AiMessage>
     */
    public function history(string $tenantId, string $conversationId): array;
}
