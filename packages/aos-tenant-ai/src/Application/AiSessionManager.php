<?php

declare(strict_types=1);

namespace DressnMore\Aos\TenantAi\Application;

use DressnMore\Aos\TenantAi\Contracts\SessionProviderInterface;
use DressnMore\Aos\TenantAi\Domain\Session\AiSession;

final class AiSessionManager
{
    public function __construct(private readonly SessionProviderInterface $sessions) {}

    public function start(AiSession $session): AiSession
    {
        return $this->sessions->start($session);
    }

    public function current(string $tenantId, string $userId): ?AiSession
    {
        return $this->sessions->current($tenantId, $userId);
    }

    public function attachConversation(AiSession $session, string $conversationId): AiSession
    {
        return $this->sessions->update($session->withConversation($conversationId));
    }

    public function focus(AiSession $session, ?string $intent, ?string $capability, ?string $tool): AiSession
    {
        return $this->sessions->update($session->withFocus($intent, $capability, $tool));
    }
}
