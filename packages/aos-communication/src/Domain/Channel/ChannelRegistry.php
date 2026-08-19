<?php

declare(strict_types=1);

namespace DressnMore\Aos\Communication\Domain\Channel;

use DressnMore\Aos\Communication\Contracts\ChannelAdapterInterface;

final class ChannelRegistry implements ChannelRegistryInterface
{
    /** @var array<string, ChannelAccount> */
    private array $accounts = [];
    /** @var array<string, ChannelAdapterInterface> */
    private array $adapters = [];

    public function register(ChannelAccount $account, ChannelAdapterInterface $adapter): void
    {
        $key = $this->key($account->channelType(), $account->tenantId());
        $this->accounts[$key] = $account;
        $this->adapters[$key] = $adapter;
    }

    public function account(ChannelType $type, ?string $tenantId = null): ?ChannelAccount
    {
        return $this->accounts[$this->key($type, $tenantId)] ?? $this->accounts[$this->key($type, null)] ?? null;
    }

    public function adapter(ChannelType $type, ?string $tenantId = null): ?ChannelAdapterInterface
    {
        return $this->adapters[$this->key($type, $tenantId)] ?? $this->adapters[$this->key($type, null)] ?? null;
    }

    public function all(): array
    {
        return array_values($this->accounts);
    }

    private function key(ChannelType $type, ?string $tenantId): string
    {
        return $type->value.'::'.($tenantId ?? 'global');
    }
}
