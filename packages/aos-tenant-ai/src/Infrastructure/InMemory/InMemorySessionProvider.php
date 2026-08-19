<?php

declare(strict_types=1);

namespace DressnMore\Aos\TenantAi\Infrastructure\InMemory;

use DressnMore\Aos\TenantAi\Contracts\SessionProviderInterface;
use DressnMore\Aos\TenantAi\Domain\Session\AiSession;

/** Test/demo only. */
final class InMemorySessionProvider implements SessionProviderInterface
{
    /** @var array<string, AiSession> */
    private array $byUser = [];

    private function key(string $tenantId, string $userId): string
    {
        return $tenantId.':'.$userId;
    }

    public function start(AiSession $session): AiSession
    {
        $userId = $session->userId() ?? 'anonymous';
        $this->byUser[$this->key($session->tenantId(), $userId)] = $session;

        return $session;
    }

    public function current(string $tenantId, string $userId): ?AiSession
    {
        return $this->byUser[$this->key($tenantId, $userId)] ?? null;
    }

    public function update(AiSession $session): AiSession
    {
        return $this->start($session);
    }
}
