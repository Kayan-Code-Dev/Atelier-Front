<?php

declare(strict_types=1);

namespace DressnMore\Aos\TenantAi\Contracts;

use DressnMore\Aos\TenantAi\Domain\Memory\TenantConversationMemory;

interface MemoryProviderInterface
{
    public function get(string $tenantId): ?TenantConversationMemory;

    public function save(TenantConversationMemory $memory): TenantConversationMemory;
}
