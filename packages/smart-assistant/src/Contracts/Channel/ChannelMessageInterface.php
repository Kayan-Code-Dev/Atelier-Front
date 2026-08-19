<?php

declare(strict_types=1);

namespace DressnMore\SmartAssistant\Contracts\Channel;

interface ChannelMessageInterface
{
    public function channelId(): string;

    public function externalId(): ?string;

    public function body(): string;

    /**
     * @return array<string, mixed>
     */
    public function payload(): array;
}
