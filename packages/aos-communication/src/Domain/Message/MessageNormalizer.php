<?php

declare(strict_types=1);

namespace DressnMore\Aos\Communication\Domain\Message;

use DressnMore\Aos\Communication\Contracts\ChannelAdapterInterface;

final class MessageNormalizer
{
    /**
     * @param array<string, mixed> $payload
     */
    public function normalize(array $payload, ChannelAdapterInterface $adapter): NormalizedMessage
    {
        return $adapter->normalizeInbound($payload);
    }
}
