<?php

declare(strict_types=1);

namespace DressnMore\Aos\Communication\Domain\Channel;

use DressnMore\Aos\Communication\Contracts\ChannelAdapterInterface;

final class ChannelManager
{
    public function __construct(private readonly ChannelRegistryInterface $registry) {}

    public function register(ChannelAccount $account, ChannelAdapterInterface $adapter): void
    {
        $this->registry->register($account, $adapter);
    }

    public function resolveAdapter(ChannelType $channel, ?string $tenantId = null): ?ChannelAdapterInterface
    {
        return $this->registry->adapter($channel, $tenantId);
    }
}
