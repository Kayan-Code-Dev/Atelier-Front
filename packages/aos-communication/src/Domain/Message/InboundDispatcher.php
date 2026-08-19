<?php

declare(strict_types=1);

namespace DressnMore\Aos\Communication\Domain\Message;

use DressnMore\Aos\Communication\Domain\Pipeline\MessagePipeline;
use DressnMore\Aos\Communication\Domain\Pipeline\MessagePipelineBag;

final class InboundDispatcher
{
    public function __construct(private readonly MessagePipeline $pipeline) {}

    /**
     * @param array<string,mixed> $payload
     */
    public function dispatch(array $payload, ?string $tenantId = null): MessagePipelineBag
    {
        return $this->pipeline->process(new MessagePipelineBag($payload, $tenantId));
    }
}
