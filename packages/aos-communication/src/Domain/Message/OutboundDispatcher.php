<?php

declare(strict_types=1);

namespace DressnMore\Aos\Communication\Domain\Message;

use DressnMore\Aos\Communication\Domain\Channel\ChannelRegistryInterface;

final class OutboundDispatcher
{
    public function __construct(private readonly ChannelRegistryInterface $registry) {}

    public function dispatch(NormalizedMessage $message): bool
    {
        $adapter = $this->registry->adapter($message->channel(), $message->tenantId());
        if ($adapter === null) {
            return false;
        }

        return $adapter->sendOutbound($message);
    }
}
