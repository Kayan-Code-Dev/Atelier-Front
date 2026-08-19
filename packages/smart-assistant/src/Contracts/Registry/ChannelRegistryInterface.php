<?php

declare(strict_types=1);

namespace DressnMore\SmartAssistant\Contracts\Registry;

use DressnMore\SmartAssistant\Contracts\Channel\ChannelInterface;

interface ChannelRegistryInterface
{
    public function register(ChannelInterface $channel): void;

    public function get(string $channelId): ?ChannelInterface;

    public function has(string $channelId): bool;

    /**
     * @return list<ChannelInterface>
     */
    public function all(): array;
}
