<?php

declare(strict_types=1);

namespace DressnMore\Aos\TenantAi\Contracts;

use DressnMore\Aos\TenantAi\Domain\Session\AiSession;

interface SessionProviderInterface
{
    public function start(AiSession $session): AiSession;

    public function current(string $tenantId, string $userId): ?AiSession;

    public function update(AiSession $session): AiSession;
}
