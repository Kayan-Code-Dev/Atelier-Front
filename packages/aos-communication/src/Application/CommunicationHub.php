<?php

declare(strict_types=1);

namespace DressnMore\Aos\Communication\Application;

use DressnMore\Aos\Communication\Contracts\CommunicationHubInterface;
use DressnMore\Aos\Communication\Domain\Message\InboundDispatcher;
use DressnMore\Aos\Communication\Domain\Message\NormalizedMessage;
use DressnMore\Aos\Communication\Domain\Message\OutboundDispatcher;
use DressnMore\Aos\Communication\Domain\Pipeline\MessagePipelineBag;

final class CommunicationHub implements CommunicationHubInterface
{
    public function __construct(
        private readonly InboundDispatcher $inboundDispatcher,
        private readonly OutboundDispatcher $outboundDispatcher,
    ) {}

    public function receive(array $payload, ?string $tenantId = null): MessagePipelineBag
    {
        return $this->inboundDispatcher->dispatch($payload, $tenantId);
    }

    public function send(NormalizedMessage $message): bool
    {
        return $this->outboundDispatcher->dispatch($message);
    }
}
