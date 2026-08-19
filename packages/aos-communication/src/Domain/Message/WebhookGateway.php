<?php

declare(strict_types=1);

namespace DressnMore\Aos\Communication\Domain\Message;

use DressnMore\Aos\Communication\Domain\Channel\ChannelResolver;
use DressnMore\Aos\Communication\Domain\Channel\ChannelRegistryInterface;

final class WebhookGateway
{
    public function __construct(
        private readonly ChannelResolver $resolver,
        private readonly ChannelRegistryInterface $registry,
    ) {}

    /**
     * @param array<string,mixed> $payload
     */
    public function validate(array $payload, ?string $tenantId = null): bool
    {
        $channel = $this->resolver->resolve($payload, $tenantId);
        if ($channel === null) {
            return false;
        }

        $adapter = $this->registry->adapter($channel, $tenantId);

        return $adapter?->validateWebhook($payload) ?? false;
    }
}
