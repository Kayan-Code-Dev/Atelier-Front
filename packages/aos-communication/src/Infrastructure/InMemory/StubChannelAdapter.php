<?php

declare(strict_types=1);

namespace DressnMore\Aos\Communication\Infrastructure\InMemory;

use DressnMore\Aos\Communication\Contracts\ChannelAdapterInterface;
use DressnMore\Aos\Communication\Domain\Channel\ChannelType;
use DressnMore\Aos\Communication\Domain\Factory\CommunicationFactory;
use DressnMore\Aos\Communication\Domain\Message\NormalizedMessage;

final class StubChannelAdapter implements ChannelAdapterInterface
{
    public function __construct(
        private readonly ChannelType $channelType,
        private readonly CommunicationFactory $factory = new CommunicationFactory(),
    ) {}

    public function channelType(): ChannelType
    {
        return $this->channelType;
    }

    public function normalizeInbound(array $payload): NormalizedMessage
    {
        $payload['channel'] = $this->channelType->value;

        return $this->factory->fromPayload($payload);
    }

    public function sendOutbound(NormalizedMessage $message, array $options = []): bool
    {
        return $message->channel() === $this->channelType;
    }

    public function validateWebhook(array $payload): bool
    {
        return isset($payload['channel']) && (string) $payload['channel'] === $this->channelType->value;
    }
}
