<?php

declare(strict_types=1);

namespace DressnMore\Aos\TenantAi\Contracts;

use DressnMore\Aos\TenantAi\Domain\Context\TenantAiContext;
use DressnMore\Aos\TenantAi\Domain\Session\AiSession;

interface ContextProviderInterface
{
    public function build(AiSession $session, string $role, array $permissions = [], ?string $country = null): TenantAiContext;
}
