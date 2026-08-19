<?php

declare(strict_types=1);

namespace DressnMore\Aos\TenantAi\Application;

use DressnMore\Aos\TenantAi\Contracts\MemoryProviderInterface;
use DressnMore\Aos\TenantAi\Domain\Memory\TenantConversationMemory;

final class ConversationMemoryService
{
    public function __construct(private readonly MemoryProviderInterface $memory) {}

    public function getOrDefault(string $tenantId): TenantConversationMemory
    {
        return $this->memory->get($tenantId) ?? new TenantConversationMemory($tenantId);
    }

    public function save(TenantConversationMemory $memory): TenantConversationMemory
    {
        return $this->memory->save($memory);
    }
}
