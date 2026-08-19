<?php

declare(strict_types=1);

namespace DressnMore\SmartAssistant\Registry;

use DressnMore\SmartAssistant\Contracts\Channel\ChannelInterface;
use DressnMore\SmartAssistant\Contracts\Registry\ChannelRegistryInterface;
use LogicException;

final class InMemoryChannelRegistry implements ChannelRegistryInterface
{
    /** @var array<string, ChannelInterface> */
    private array $items = [];

    public function register(ChannelInterface $channel): void
    {
        $id = $channel->identity()->id();
        if (isset($this->items[$id])) {
            throw new LogicException('Channel already registered: '.$id);
        }
        $this->items[$id] = $channel;
    }

    public function get(string $channelId): ?ChannelInterface
    {
        return $this->items[$channelId] ?? null;
    }

    public function has(string $channelId): bool
    {
        return isset($this->items[$channelId]);
    }

    public function all(): array
    {
        return array_values($this->items);
    }
}
