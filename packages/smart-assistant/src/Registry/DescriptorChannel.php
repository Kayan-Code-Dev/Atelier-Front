<?php

declare(strict_types=1);

namespace DressnMore\SmartAssistant\Registry;

use DressnMore\SmartAssistant\Contracts\Channel\ChannelInterface;
use DressnMore\SmartAssistant\Domain\Channel\Channel;

/** Descriptor stub for registry validation — not a live channel adapter. */
final class DescriptorChannel implements ChannelInterface
{
    public function __construct(private readonly Channel $channel) {}

    public function identity(): Channel
    {
        return $this->channel;
    }

    public function type(): string
    {
        return $this->channel->type();
    }
}
