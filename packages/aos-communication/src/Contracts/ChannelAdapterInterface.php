<?php

declare(strict_types=1);

namespace DressnMore\Aos\Communication\Contracts;

use DressnMore\Aos\Communication\Domain\Channel\ChannelType;
use DressnMore\Aos\Communication\Domain\Message\NormalizedMessage;

interface ChannelAdapterInterface
{
    public function channelType(): ChannelType;

    /**
     * @param array<string, mixed> $payload
     */
    public function normalizeInbound(array $payload): NormalizedMessage;

    /**
     * Conceptual send only; no SDK/HTTP.
     *
     * @param array<string, scalar|null> $options
     */
    public function sendOutbound(NormalizedMessage $message, array $options = []): bool;

    /**
     * @param array<string, mixed> $payload
     */
    public function validateWebhook(array $payload): bool;
}
