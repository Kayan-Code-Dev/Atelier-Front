<?php

declare(strict_types=1);

namespace DressnMore\Aos\TenantAi\Infrastructure\InMemory;

use DressnMore\Aos\TenantAi\Contracts\MemoryProviderInterface;
use DressnMore\Aos\TenantAi\Domain\Memory\TenantConversationMemory;

/** Test/demo only. */
final class InMemoryMemoryProvider implements MemoryProviderInterface
{
    /** @var array<string, TenantConversationMemory> */
    private array $items = [];

    public function get(string $tenantId): ?TenantConversationMemory
    {
        return $this->items[$tenantId] ?? null;
    }

    public function save(TenantConversationMemory $memory): TenantConversationMemory
    {
        $this->items[$memory->tenantId()] = $memory;

        return $memory;
    }
}
