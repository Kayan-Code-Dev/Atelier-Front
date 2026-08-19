<?php

declare(strict_types=1);

namespace DressnMore\Aos\Communication\Domain\Channel;

use DressnMore\Aos\Communication\Contracts\ChannelAdapterInterface;

interface ChannelRegistryInterface
{
    public function register(ChannelAccount $account, ChannelAdapterInterface $adapter): void;
    public function account(ChannelType $type, ?string $tenantId = null): ?ChannelAccount;
    public function adapter(ChannelType $type, ?string $tenantId = null): ?ChannelAdapterInterface;
    /** @return list<ChannelAccount> */
    public function all(): array;
}
