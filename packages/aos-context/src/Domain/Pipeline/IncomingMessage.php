<?php

declare(strict_types=1);

namespace DressnMore\Aos\Context\Domain\Pipeline;

use DressnMore\Aos\Context\Domain\Identity\ChannelType;
use DressnMore\Aos\Context\Domain\Identity\ExternalIdentifier;

/**
 * Inbound message envelope feeding the Context Pipeline (channel-agnostic).
 */
final class IncomingMessage
{
    /**
     * @param  array<string, scalar|null>  $attributes
     */
    public function __construct(
        private readonly ChannelType $channelType,
        private readonly ExternalIdentifier $externalIdentifier,
        private readonly string $channelAccount,
        private readonly ?string $text = null,
        private readonly ?string $conversationHint = null,
        private readonly array $attributes = [],
    ) {}

    public function channelType(): ChannelType
    {
        return $this->channelType;
    }

    public function externalIdentifier(): ExternalIdentifier
    {
        return $this->externalIdentifier;
    }

    public function channelAccount(): string
    {
        return $this->channelAccount;
    }

    public function text(): ?string
    {
        return $this->text;
    }

    public function conversationHint(): ?string
    {
        return $this->conversationHint;
    }

    /**
     * @return array<string, scalar|null>
     */
    public function attributes(): array
    {
        return $this->attributes;
    }
}
